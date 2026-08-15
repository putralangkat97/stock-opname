<?php

namespace App\Enums;

enum StockOpnameStatus: string
{
    case STATUS_DRAFT = 'Draft';
    case STATUS_IN_PROGRESS = 'In Progress';
    case STATUS_COMPLETED = 'Completed';
    case STATUS_APPROVED = 'Approved';

    // No STATUS_REJECTED case — reject() transitions a Completed opname back
    // to In Progress for a recount, it never assigns a terminal "Rejected"
    // status. Keeping an unused case here was a landmine: StockOpname::state()
    // has no match arm for it, so if it were ever assigned some other way
    // (direct DB write, a future feature, a test factory state), state()
    // would throw rather than degrade gracefully. If you want a genuine
    // terminal "permanently cancel this opname" feature later, add this case
    // back along with a real RejectedState class at the same time — don't
    // let the enum imply a state the state machine doesn't actually support.
}
