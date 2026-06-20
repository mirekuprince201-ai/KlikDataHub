<?php

namespace App\Http\Controllers\Admin\AfaOrders;

class DeliveredAfaOrderController extends BaseAfaOrderController
{
    public function index()
    {
        return $this->view('delivered', 'Delivered AFA Orders');
    }
}