<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;

class OrderController extends Controller
{
    //
    public function addOrder()
    {
        $products = Product::orderBy('created_at', 'DESC')->get();
        return view('orders.add', compact('products'));
        
    }

    public function getProduct($id)
    {
        $products = Product::findOrFail($id);
        return response()->json($products, 200);
    }

    public function addToCart(Request $request) 
    {
        //validasi data yang diterima
        //dari ajax request addToCart mengirimkan product_id dan qty

        $this->validate($request, [
            'product_id' => 'required|exists:product, id',
            'qty' => 'required|integer'
        ]);
        print_r($request);
        //mengambil data product berdasarkan id
        $product = Product::findOrFail($request->product_id);
        //mengambl cookie cart dengan $request->cookie('cart')
        $getCart = json_decode($request->cookie('cart'), true);

        //jika datanya ada
        if ($getCart) {
            //jika keynya exist berdasarkan product_id
            if (array_key_exists($request->product_id, $getCart)) {
                //jumlahkan qty barangnya
                $getCart[$request->product_id]['qty'] += $request->qty;
                //dikirim kembali untuk disimpan di cookie
                return response()->json($getCart, 200)
                    ->cookie('cart', json_encode($getCart), 120);
            }
        }


        //jika cart kosong tambahkan cart baru
        $getCart[$request->product_id] = [
            'code' => $product->code,
            'name' => $product->name,
            'price' => $product->price,
            'qty' => $product->qty
        ];

        //kirim responnya kemudian simpan di cookie
        return response()->json($getCart, 200)
            ->cookie('cart', json_encode($getCart), 120);
    }

    public function getCart()
    {
        // mengambil cart dari cookie
        $cart = json_decode(request()->cookie('cart', true));
        dd($cart);
        //mengirimkan kembali dalam bentuk json untuk ditampilkan dalam vue
        return response()->json($cart, 200);
    }

    public function removeCart($id)
    {
        $cart = json_decode(request()->cookie('cart'), true);
        //menghapus cart berdasarkan product id
        unset($cart[$id]);

        //cart diperbaharui
        return response()->json($cart, 200)->cookie('cart', json_encode($cart), 120);
    }

    public function checkout()
    {
        return view('orders.checkout');
    }

}
