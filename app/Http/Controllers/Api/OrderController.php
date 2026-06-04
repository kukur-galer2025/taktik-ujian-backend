<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Bundle;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    // User: Create order (checkout)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bundle_id' => 'nullable|exists:bundles,id',
            'tryout_id' => 'nullable|exists:tryouts,id',
            'voucher_code' => 'nullable|string',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if (empty($validated['bundle_id']) && empty($validated['tryout_id'])) {
            return response()->json(['message' => 'Harus memilih bundle atau tryout.'], 422);
        }

        $amount = 0;
        if (!empty($validated['bundle_id'])) {
            $bundle = Bundle::findOrFail($validated['bundle_id']);
            $amount = $bundle->discount_price ?? $bundle->price;
        } else {
            $tryout = \App\Models\Tryout::findOrFail($validated['tryout_id']);
            $amount = $tryout->price;
        }

        $discount = 0;
        $voucherCode = null;

        // Apply voucher if provided
        if (!empty($validated['voucher_code'])) {
            $voucher = Voucher::where('code', strtoupper($validated['voucher_code']))->first();
            if ($voucher && $voucher->isValid()) {
                $discount = $voucher->calculateDiscount($amount);
                $voucherCode = $voucher->code;
            }
        }

        $finalAmount = max(0, $amount - $discount);

        // Store payment proof
        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')->store('payment_proofs', 'public');
            $proofPath = '/storage/' . $path;
        }

        $order = Order::create([
            'user_id' => $request->user()->id,
            'bundle_id' => $validated['bundle_id'] ?? null,
            'tryout_id' => $validated['tryout_id'] ?? null,
            'amount' => $amount,
            'voucher_code' => $voucherCode,
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'payment_proof' => $proofPath,
            'status' => 'pending',
        ]);

        // Increment voucher usage
        if ($voucherCode) {
            Voucher::where('code', $voucherCode)->increment('used_count');
        }

        return response()->json([
            'message' => 'Pesanan berhasil dibuat! Menunggu konfirmasi admin.',
            'order' => $order->load(['bundle', 'tryout']),
        ]);
    }

    // User: Get their order history
    public function myOrders(Request $request)
    {
        $orders = Order::with(['bundle', 'tryout'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    // Validate voucher (preview discount)
    public function validateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|integer|min:0',
        ]);

        $voucher = Voucher::where('code', strtoupper($request->code))->first();

        if (!$voucher) {
            return response()->json(['message' => 'Kode voucher tidak ditemukan.'], 404);
        }

        if (!$voucher->isValid()) {
            return response()->json(['message' => 'Voucher sudah tidak berlaku atau sudah habis.'], 422);
        }

        if ($request->amount < $voucher->min_purchase) {
            return response()->json([
                'message' => 'Minimum pembelian untuk voucher ini adalah Rp ' . number_format($voucher->min_purchase, 0, ',', '.'),
            ], 422);
        }

        $discount = $voucher->calculateDiscount($request->amount);

        return response()->json([
            'valid' => true,
            'code' => $voucher->code,
            'discount_type' => $voucher->discount_type,
            'discount_value' => $voucher->discount_value,
            'discount' => $discount,
            'final_amount' => max(0, $request->amount - $discount),
        ]);
    }

    // Admin: Get all orders
    public function adminIndex()
    {
        $orders = Order::with(['user:id,name,email', 'bundle:id,title', 'tryout:id,title'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    // Admin: Confirm or reject order
    public function adminUpdateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,rejected',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $order = Order::findOrFail($id);
        
        // If order is rejected, refund the voucher if any
        if ($request->status === 'rejected' && $order->status !== 'rejected') {
            if ($order->voucher_code) {
                Voucher::where('code', $order->voucher_code)->decrement('used_count');
            }
        }

        $order->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui.',
            'order' => $order->load(['user:id,name,email', 'bundle:id,title', 'tryout:id,title']),
        ]);
    }

    // User: Re-upload Payment Proof for Rejected Orders
    public function reuploadPaymentProof(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
            
        if ($order->status !== 'rejected') {
            return response()->json(['message' => 'Hanya pesanan yang ditolak yang dapat diunggah ulang.'], 403);
        }

        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Re-validate voucher if it exists
        if ($order->voucher_code) {
            $voucher = Voucher::where('code', $order->voucher_code)->first();
            if (!$voucher || !$voucher->isValid()) {
                return response()->json(['message' => 'Voucher sudah tidak berlaku atau limit habis. Silakan buat pesanan baru.'], 422);
            }
            $voucher->increment('used_count');
        }

        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        $order->update([
            'payment_proof' => '/storage/' . $path,
            'status' => 'pending',
            'admin_notes' => null, // clear previous notes
        ]);

        return response()->json([
            'message' => 'Bukti pembayaran berhasil diunggah ulang. Menunggu konfirmasi admin.',
            'order' => $order->load(['bundle', 'tryout'])
        ]);
    }

    // Admin: Voucher CRUD
    public function getVouchers()
    {
        return response()->json(Voucher::orderBy('created_at', 'desc')->get());
    }

    public function createVoucher(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_purchase' => 'nullable|integer|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['min_purchase'] = $validated['min_purchase'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $voucher = Voucher::create($validated);
        return response()->json(['message' => 'Voucher berhasil dibuat.', 'voucher' => $voucher]);
    }

    public function updateVoucher(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $id,
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_purchase' => 'nullable|integer|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $voucher->update($validated);
        return response()->json(['message' => 'Voucher berhasil diperbarui.', 'voucher' => $voucher]);
    }

    public function deleteVoucher($id)
    {
        Voucher::findOrFail($id)->delete();
        return response()->json(['message' => 'Voucher berhasil dihapus.']);
    }

    // User: Get available (public) vouchers
    public function getAvailableVouchers()
    {
        $vouchers = Voucher::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($vouchers);
    }
    
    // User: Get Invoice
    public function getInvoice(Request $request, $id)
    {
        $order = Order::with(['user', 'bundle', 'tryout'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
            
        if ($order->status !== 'confirmed') {
            return response()->json(['message' => 'Invoice belum tersedia karena pesanan belum dikonfirmasi.'], 403);
        }
        
        return response()->json([
            'invoice_number' => 'INV-' . date('Ymd', strtotime($order->created_at)) . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT),
            'date' => $order->created_at->format('Y-m-d H:i:s'),
            'user' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'item' => [
                'name' => $order->bundle ? $order->bundle->title : ($order->tryout ? $order->tryout->title : 'Paket Ujian'),
                'type' => $order->bundle ? 'Bundle' : 'Tryout',
                'price' => $order->amount,
            ],
            'discount' => $order->discount,
            'voucher_code' => $order->voucher_code,
            'total' => $order->final_amount,
            'status' => 'LUNAS'
        ]);
    }
}

