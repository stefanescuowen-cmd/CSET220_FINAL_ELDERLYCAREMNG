@extends('layouts.app')
@section('title', 'Invoice')
@section('content')<h1>Patient invoice</h1><p>Billing period: {{ $invoice->period_start }} to {{ $invoice->period_end }}</p><p>Total: ${{ number_format($invoice->total_due,2) }} · Paid: ${{ number_format($invoice->amount_paid,2) }} · {{ $invoice->status }}</p>
@if($created)<p>Invoice generated. Running Update again for this period will return this invoice without adding charges.</p>@else<p>This period has already been invoiced. No duplicate charges were added.</p>@endif
@if($invoice->status!=='Paid')<form method="post" action="{{ route('billing.payment',$invoice->id) }}">@csrf<label>Cash payment <input type="number" name="amount" min="0.01" step="0.01" max="{{ number_format($invoice->total_due-$invoice->amount_paid,2,'.','') }}" required></label><label>Notes <textarea name="notes"></textarea></label><button>Record cash payment</button></form>@endif @endsection
