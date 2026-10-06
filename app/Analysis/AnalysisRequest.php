<?php

namespace App\Analysis;

use App\Eocs\EocsOrder;
use App\Knowledge\CustomerGroup;
use App\Zammad\Ticket;

/**
 * Everything the employee chose for one analysis.
 */
final readonly class AnalysisRequest
{
    /**
     * @param  list<string>  $products
     * @param  list<EocsOrder>  $orders
     * @param  array<string, string>  $manualOrder  Order data typed in by hand when no EOCS order is loaded.
     * @param  array<string, mixed>  $formInput  The form as sent, to reopen it with the same input (PROJ-10).
     */
    public function __construct(
        public Ticket $ticket,
        public string $staffName,
        public CustomerGroup $customerGroup,
        public array $products,
        public array $orders,
        public array $manualOrder,
        public string $employeeContext,
        public ContextVariant $variant,
        public ?Summary $summary,
        public array $formInput = [],
    ) {}
}
