<?php

namespace App\Exports;

use App\Models\Deposit;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;

class DepositsExport implements FromView
{
    use Exportable;

    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Deposit::with(['user:id,name,email,trx_address'])
            ->where('status', 'Completed')
            ->whereNotNull('external_txid')
            ->whereNotIn('id', [302, 245, 279, 299, 281, 272, 262, 256, 252, 234])
            ->orderBy('created_at', 'desc');

        // Apply filters from controller
        if (!empty($this->filters['username'])) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%'.$this->filters['username'].'%');
            });
        }

        if (!empty($this->filters['txid'])) {
            $query->where('txid', 'like', '%'.$this->filters['txid'].'%');
        }

        if (!empty($this->filters['trc20_address'])) {
            $query->where('trc20_address', 'like', '%'.$this->filters['trc20_address'].'%');
        }

        if (!empty($this->filters['amount'])) {
            $val = number_format((float)$this->filters['amount'], 2, '.', '');
            $query->whereRaw('FORMAT(amount, 2) = ?', [$val]);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('created_at', [
                $this->filters['start_date'] . ' 00:00:00',
                $this->filters['end_date'] . ' 23:59:59',
            ]);
        } elseif (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        } elseif (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        $deposits = $query->get();

        return view('admin.deposits.export', compact('deposits'));
    }
}
