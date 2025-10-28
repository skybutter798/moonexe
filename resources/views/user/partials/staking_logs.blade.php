<div class="table-responsive">
  <table class="table table-sm align-middle">
    <thead class="table-light">
      <tr>
        <th>Date (NYT)</th>
        <th class="text-end">Amount Staked</th>
        <th class="text-end">Daily Rate</th>
        <th class="text-end">Profit</th>
        <th class="text-end">Running Total</th>
      </tr>
    </thead>
    <tbody>
      @php $running = 0; @endphp
      @forelse($logs as $log)
        @php $running += (float) $log->daily_profit; @endphp
        <tr>
          <td>{{ $log->created_at_nyt }}</td>
          <td class="text-end">{{ number_format((float)$log->total_balance, 2) }}</td>
          <td class="text-end">{{ number_format(((float)$log->daily_roi) * 100, 2) }}%</td>
          <td class="text-end">{{ number_format((float)$log->daily_profit, 2) }}</td>
          <td class="text-end fw-semibold">{{ number_format($running, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="text-center text-muted py-4">No ROI records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<!-- Pagination -->
<div class="mt-3 d-flex justify-content-center">
  {!! $logs->links('vendor.pagination.bootstrap-5') !!}
</div>
