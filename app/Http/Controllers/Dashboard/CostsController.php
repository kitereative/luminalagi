<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cost;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Support\Carbon;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostsController extends Controller
{
    /**
     * Show details of all created costs/expenses
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $check =  Cost::first();
        if ($check) {
            $least_year = (int) (new Carbon(
                Cost::orderBy('billing_month')
                    ->first()
                    ->billing_month
            )
            )->format('Y');
    
            $cost = Cost::filter(request(['month', 'year']))
                ->orderBy('billing_month', 'desc')
                ->paginate(12)
                ->withQueryString();
        } else {
            $cost = null;
            $least_year = null;

        }

        return view('dashboard.costs', [
            'costs'       => $cost,
            // 'costs'       => Cost::orderBy('billing_month', 'desc')->paginate(12),

            
            // 'invoices'    => $invoices,
            'years'       => (int) date('Y') - $least_year,
        ]);
    }

    /**
     * Validate cost data and create new record in database
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'billing_month' => ['required', 'date_format:Y-m-d'],
            'amount'        => ['required', 'numeric', 'min:1', 'max:4294967295'],
            'balance'       => ['required', 'numeric', 'min:1', 'max:4294967295'],
        ]);

        [$year, $month, $date] = explode('-', $validated['billing_month']);

        // Can only add costs upto current month!
        if (($year > (int) date('Y')) || (($year == (int) date('Y')) && ($month > (int) date('m'))))
            return redirect()
                ->back()
                ->with('message', 'Cost for upcoming months cannot be created in advance!');

        $expenseExists = Cost::query()
            ->whereYear('billing_month', $year)
            ->whereMonth('billing_month', $month)
            ->first();

        // Only one cost per month!
        if ($expenseExists)
            return redirect()
                ->back()
                ->with('message', 'Cost for selected month already exists!');

        Cost::create([
            'billing_month' => sprintf('%s-%s-%s', $year, $month, $date),
            'amount'        => $validated['amount'],
            'balance'       => $validated['balance']
        ]);

        return redirect()
            ->route('dashboard.costs')
            ->with('created', 'New expense was created successfully!');
    }

    /**
     * Validate cost data and update data in database
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Cost  $cost
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Cost $cost): RedirectResponse
    {
        $validated = $request->validate([
            'amount'  => ['required', 'numeric', 'min:1', 'max:4294967295'],
            'balance' => ['required', 'numeric', 'min:1', 'max:4294967295'],
        ]);

        $cost->update([
            'amount'  => $validated['amount'],
            'balance' => $validated['balance']
        ]);

        return redirect()
            ->route('dashboard.costs')
            ->with('updated', 'The expense was updated successfully!');
    }

    /**
     * Delete the specified cost's record from database
     *
     * @param \App\Models\Cost  $cost
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Cost $cost): RedirectResponse
    {
        $cost->delete();

        return redirect()
            ->route('dashboard.costs')
            ->with('deleted', 'New expense was deleted successfully!');
    }
}
