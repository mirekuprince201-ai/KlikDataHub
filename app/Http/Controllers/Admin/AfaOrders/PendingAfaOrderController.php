<?php

namespace App\Http\Controllers\Admin\AfaOrders;

class PendingAfaOrderController extends BaseAfaOrderController
{
    public function index()
    {
        return $this->view('pending', 'Pending AFA Orders');
    }
}