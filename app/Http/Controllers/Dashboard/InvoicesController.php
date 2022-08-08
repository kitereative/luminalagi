<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoicesController extends Controller
{
    /**
     * Show the invoices listing page
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $check =  Invoice::first();
        
        // dd($check);
        if ($check) {
            $least_year = (int) (new Carbon(
                Invoice::orderBy('paid_on')
                    ->first()
                    ->paid_on
            )
            )->format('Y');
    
            $invoices = Invoice::with('project')
                ->filter(request(['project_id', 'month', 'year']))
                ->orderBy('paid_on', 'desc')
                ->paginate(10)
                ->withQueryString();
            // dd($invoices);
        } else {
            $invoices = null;
            $least_year = null;

        }
        
        

        return view('dashboard.invoices', [
            'invoices'   => $invoices,
            'projects'   => Project::select(['id', 'name'])->get(),
            'years'      => (int) date('Y') - $least_year
        ]);
    }

    /**
     * Validate the invoice data and create new record in database.
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'project_id' => ['required', 'numeric', Rule::exists('projects', 'id')],
            'paid_on'    => ['required', 'date_format:Y-m-d'],
            'amount'     => ['required', 'numeric', 'min:1', 'max:4294967295']
        ]);

        Invoice::create([
            'project_id' => $request->project_id,
            'paid_on'    => $request->paid_on,
            'amount'     => $request->amount
        ]);
        
        // menambahkan 'totalinvoice' utk fungsi filter di project
        $project = Project::find($request->project_id);
        $project->totalinvoice += $request->amount;
        $project->save();

        return redirect()
            ->route('dashboard.invoices')
            ->with('created', 'The invoice was created successfully!');
    }

    /**
     * Validate the invoice data and update the record in database
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\Invoice  $invoice
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $request->validate([
            'paid_on' => ['required', 'date_format:Y-m-d'],
            'amount'  => ['required', 'numeric', 'min:1', 'max:4294967295']
        ]);

        // menambahkan 'totalinvoice' utk fungsi filter di project
        if ($request->amount != $invoice->amount) {
            $project = Project::find($invoice->project_id);
        // TODO: jika '$request->amount' ada, maka '$project->totalinvoice' dikurang 'invoice->amount' ditambah '$request->amount'
        $project->totalinvoice += $request->amount;
        $project->totalinvoice -= $invoice->amount;
        $project->save();
        }
        
        // menambahkan 'totalinvoice' utk fungsi filter di project

        $invoice->update([
            'paid_on'    => $request->paid_on,
            'amount'     => $request->amount
        ]);

        

        return redirect()
            ->route('dashboard.invoices')
            ->with('updated', 'The invoice was updated successfully!');
    }

    /**
     * Delete the requested invoice from database
     *
     * @param \App\Models\Invoice  $invoice
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $project = Project::find($invoice->project_id);
        $project->totalinvoice -= $invoice->amount;
        $project->save();

        $invoice->delete();
        return redirect()
            ->route('dashboard.invoices')
            ->with('deleted', 'The invoice was deleted successfully!');
    }
}
