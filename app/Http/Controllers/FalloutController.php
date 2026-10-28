<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FalloutData;
use App\Models\UploadBatch;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FalloutController extends Controller
{
    /**
     * Pastikan Witel valid dan branch tersedia.
     */
    private function branchOrFail(string $witel): Branch
    {
        abort_unless(
            in_array($witel, ['jaktim', 'jakpus', 'jaksel'], true),
            404
        );

        $branch = Branch::bySlug($witel);

        abort_unless(
            $branch,
            404,
            'Witel tidak ditemukan di database.'
        );

        return $branch;
    }

    /**
     * Samakan format status dengan data upload Excel.
     */
    private function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return strtolower(
            str_replace(
                [' ', '/', '-'],
                '_',
                $value
            )
        );
    }

    /**
     * Validasi payload tambah/edit.
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'order_id' => [
                'required',
                'string',
                'max:255',
            ],

            'deskripsi' => [
                'nullable',
                'string',
            ],

            'sto' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tanggal' => [
                'required',
                'date',
            ],

            'pic' => [
                'nullable',
                'string',
                'max:255',
            ],

            'resolved_eskalasi' => [
                'required',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'string',
                'max:100',
            ],

            'ket' => [
                'nullable',
                'string',
            ],

            'tiket_eskalasi' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);
    }

    /**
     * TAMBAH DATA
     *
     * POST /dashboard/{witel}/edit-data
     */
    public function store(Request $request, string $witel)
    {
        $branch = $this->branchOrFail($witel);
        $data = $this->validatedData($request);

        $orderId = trim((string) $data['order_id']);

        $tanggal = \Carbon\Carbon::parse(
            $data['tanggal']
        )->format('Y-m-d');

        $pic = trim((string) ($data['pic'] ?? ''));

        if ($pic === '') {
            $pic = auth()->user()->name ?? null;
        }

        $branchDuplicate = FalloutData::forWitel($witel)
            ->whereRaw('LOWER(TRIM(order_id)) = ?', [strtolower($orderId)])
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($branchDuplicate) {
            return response()->json([
                'success' => false,
                'message' => "Order ID {$orderId} sudah terdaftar pada Witel dan tanggal ini.",
                'errors' => [
                    'order_id' => ['Order ID sudah terdaftar pada Witel dan tanggal ini.'],
                ],
            ], 422);
        }

        try {
            $result = DB::transaction(function () use (
                $branch,
                $data,
                $orderId,
                $tanggal,
                $pic
            ) {
                /*
                |--------------------------------------------------------------------------
                | CARI BATCH MANUAL / BATCH YANG SESUAI
                |--------------------------------------------------------------------------
                | Tidak membuat batch baru setiap kali kalau sudah ada batch dengan
                | Witel + tanggal + PIC yang sama.
                */
                $batchQuery = UploadBatch::where(
                    'branch_id',
                    $branch->branch_id
                )->whereDate(
                    'tanggal',
                    $tanggal
                );

                if ($pic === null) {
                    $batchQuery->whereNull('pic');
                } else {
                    $batchQuery->where('pic', $pic);
                }

                $batch = $batchQuery
                    ->orderByDesc('batch_id')
                    ->first();

                if (!$batch) {
                    $batch = UploadBatch::create([
                        'branch_id'   => $branch->branch_id,
                        'tanggal'     => $tanggal,
                        'pic'         => $pic,
                        'total_data'  => 0,
                        'uploaded_by' => auth()->id(),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | CEK DUPLIKAT DI BATCH YANG SAMA
                |--------------------------------------------------------------------------
                | Sesuai constraint database:
                | batch_id + order_id harus unik.
                */
                $duplicate = FalloutData::where(
                    'batch_id',
                    $batch->batch_id
                )->where(
                    'order_id',
                    $orderId
                )->exists();

                if ($duplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => "Order ID {$orderId} sudah terdaftar pada Witel dan tanggal ini. Gunakan Order ID lain atau edit data yang sudah ada.",
                        'errors'  => [
                            'order_id' => [
                                'Order ID sudah terdaftar pada Witel dan tanggal ini.',
                            ],
                        ],
                    ], 422);
                }

                $row = FalloutData::create([
                    'batch_id'          => $batch->batch_id,
                    'order_id'          => $orderId,
                    'deskripsi'         => $data['deskripsi'] ?? null,
                    'sto'               => !empty($data['sto'])
                        ? strtoupper(trim((string) $data['sto']))
                        : null,
                    'tanggal'           => $tanggal,
                    'pic'               => $pic,
                    'resolved_eskalasi' => strtoupper((string) $this->normalize($data['resolved_eskalasi'] ?? null)),
                    'status'            => $this->normalize(
                        $data['status'] ?? null
                    ),
                    'ket' => $data['ket'] ?? null,
                    'tiket_eskalasi' => trim((string) ($data['tiket_eskalasi'] ?? '')) ?: null,
                    'uploaded_by'       => auth()->id(),
                    'uploaded_at'       => now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | SINKRONKAN JUMLAH BATCH
                |--------------------------------------------------------------------------
                */
                $batch->update([
                    'total_data' => FalloutData::where(
                        'batch_id',
                        $batch->batch_id
                    )->count(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Data {$orderId} berhasil ditambahkan.",
                    'data'    => [
                        'row_id'    => $row->row_id,
                        'batch_id'  => $row->batch_id,
                        'order_id'  => $row->order_id,
                    ],
                ], 201);
            });

            return $result;

        } catch (QueryException $e) {
            Log::error(
                'Gagal menambah FalloutData',
                [
                    'witel'    => $witel,
                    'order_id' => $orderId,
                    'error'    => $e->getMessage(),
                ]
            );

            if (
                (int) $e->getCode() === 23000
                || str_contains(
                    strtolower($e->getMessage()),
                    'duplicate'
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' => "Order ID {$orderId} sudah terdaftar pada Witel dan tanggal ini. Gunakan Order ID lain atau edit data yang sudah ada.",
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Data gagal disimpan ke database. Coba lagi.',
            ], 500);

        } catch (Throwable $e) {
            Log::error(
                'Gagal menambah FalloutData',
                [
                    'witel'    => $witel,
                    'order_id' => $orderId,
                    'error'    => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data. Coba lagi.',
            ], 500);
        }
    }

    /**
     * UPDATE DATA
     *
     * PUT /dashboard/{witel}/edit-data/{id}
     */
    public function update(Request $request, string $witel, int $id)
    {
        $this->branchOrFail($witel);
        $data = $this->validatedData($request);

        $row = FalloutData::forWitel($witel)
            ->where('row_id', $id)
            ->first();

        abort_unless(
            $row,
            404,
            'Data Fallout tidak ditemukan.'
        );

        $orderId = trim((string) $data['order_id']);

        $tanggal = \Carbon\Carbon::parse(
            $data['tanggal']
        )->format('Y-m-d');

        $pic = trim((string) ($data['pic'] ?? ''));

        if ($pic === '') {
            $pic = auth()->user()->name ?? null;
        }

        try {
            $duplicate = FalloutData::forWitel($witel)
                ->whereRaw('LOWER(TRIM(order_id)) = ?', [strtolower($orderId)])
                ->whereDate('tanggal', $tanggal)
                ->where('row_id', '!=', $row->row_id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => "Order ID {$orderId} sudah terdaftar pada Witel dan tanggal ini.",
                    'errors'  => [
                        'order_id' => [
                            'Order ID sudah terdaftar pada Witel dan tanggal ini.',
                        ],
                    ],
                ], 422);
            }

            $row->update([
                'order_id'          => $orderId,
                'deskripsi'         => $data['deskripsi'] ?? null,
                'sto'               => !empty($data['sto'])
                    ? strtoupper(trim((string) $data['sto']))
                    : null,
                'tanggal'           => $tanggal,
                'pic'               => $pic,
                'resolved_eskalasi' => strtoupper((string) $this->normalize($data['resolved_eskalasi'] ?? null)),
                'status'            => $this->normalize(
                    $data['status'] ?? null
                ),
                'ket' => $data['ket'] ?? null,
                    'tiket_eskalasi' => trim((string) ($data['tiket_eskalasi'] ?? '')) ?: null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | JUMLAH BATCH TETAP SINKRON
            |--------------------------------------------------------------------------
            */
            if ($row->batch_id) {
                $batch = UploadBatch::where(
                    'batch_id',
                    $row->batch_id
                )->first();

                if ($batch) {
                    $batch->update([
                        'total_data' => FalloutData::where(
                            'batch_id',
                            $row->batch_id
                        )->count(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Data {$orderId} berhasil diperbarui.",
            ]);

        } catch (QueryException $e) {
            Log::error(
                'Gagal update FalloutData',
                [
                    'witel'    => $witel,
                    'row_id'   => $id,
                    'order_id' => $orderId,
                    'error'    => $e->getMessage(),
                ]
            );

            if (
                (int) $e->getCode() === 23000
                || str_contains(
                    strtolower($e->getMessage()),
                    'duplicate'
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' => "Order ID {$orderId} sudah terdaftar pada Witel dan tanggal ini.",
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Data gagal diperbarui. Coba lagi.',
            ], 500);

        } catch (Throwable $e) {
            Log::error(
                'Gagal update FalloutData',
                [
                    'witel'  => $witel,
                    'row_id' => $id,
                    'error'  => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data. Coba lagi.',
            ], 500);
        }
    }

    /**
     * HAPUS SATU DATA
     *
     * DELETE /dashboard/{witel}/edit-data/{id}
     */
    public function destroy(string $witel, int $id)
    {
        $this->branchOrFail($witel);

        $row = FalloutData::forWitel($witel)
            ->where('row_id', $id)
            ->first();

        abort_unless(
            $row,
            404,
            'Data Fallout tidak ditemukan.'
        );

        $orderId = $row->order_id;
        $batchId = $row->batch_id;

        try {
            DB::transaction(function () use ($row, $batchId) {
                $row->delete();

                if (!$batchId) {
                    return;
                }

                $batch = UploadBatch::where(
                    'batch_id',
                    $batchId
                )->first();

                if (!$batch) {
                    return;
                }

                $remaining = FalloutData::where(
                    'batch_id',
                    $batchId
                )->count();

                if ($remaining <= 0) {
                    $batch->delete();
                } else {
                    $batch->update([
                        'total_data' => $remaining,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Data {$orderId} berhasil dihapus dan riwayat penghapusannya telah dicatat.",
            ]);

        } catch (Throwable $e) {
            Log::error(
                'Gagal delete FalloutData',
                [
                    'witel'  => $witel,
                    'row_id' => $id,
                    'error'  => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Data gagal dihapus. Coba lagi.',
            ], 500);
        }
    }
}
