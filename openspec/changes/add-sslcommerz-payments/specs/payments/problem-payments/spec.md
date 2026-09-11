## Purpose

Keeps a durable record of payment attempts that failed for a reason the customer cannot resolve, and
gives staff a way to recover the money and settle the order by hand.

## ADDED Requirements

### Requirement: Failed payment attempts are recorded

The system SHALL record a problem payment whenever a payment attempt fails for one of these reasons:

- **declined** — the gateway reports the transaction failed or was declined
- **validation_mismatch** — a callback could not be validated: the gateway reports it invalid, the amount
  or currency does not match the order, the signature does not verify, or the transaction reference
  matches no order
- **initiation_error** — the request to create a payment session errored, timed out, or returned a
  response that carried no payment page URL

Each record SHALL capture the order it relates to (when known), the gateway, the transaction reference,
the reason category, a human-readable message, the amount the gateway reported, the currency, and the raw
gateway payload. Customer cancellation of the hosted payment page is NOT a failure and MUST NOT be
recorded.

#### Scenario: Gateway declines the transaction

- **WHEN** the gateway reports a transaction as failed for an order
- **THEN** a problem payment is recorded for that order with reason `declined`
- **AND** it holds the transaction reference and the raw gateway payload

#### Scenario: Validated amount does not match

- **WHEN** a callback validates at 1.00 BDT against an order whose grand total is 4,250.00 BDT
- **THEN** a problem payment is recorded with reason `validation_mismatch`
- **AND** its message names both the expected and the received amount

#### Scenario: Session request times out

- **WHEN** the request to create a payment session times out
- **THEN** a problem payment is recorded for that order with reason `initiation_error`
- **AND** the customer is told the payment could not be started and the order is still theirs to pay

#### Scenario: Callback for an unknown order

- **WHEN** a callback arrives whose transaction reference matches no order
- **THEN** a problem payment is recorded with reason `validation_mismatch` and no order attached

#### Scenario: Customer cancels

- **WHEN** a customer cancels on the hosted payment page
- **THEN** no problem payment is recorded

### Requirement: Problem payments carry a resolution state

Every problem payment SHALL be either unresolved or resolved. A resolved record SHALL identify who
resolved it, when, by which resolution method (`gateway_recheck` or `manual`), and carry the reference and
note supplied at resolution. An unresolved record SHALL have none of those set.

#### Scenario: Newly recorded problem

- **WHEN** a problem payment is recorded
- **THEN** it is unresolved, with no resolver, no resolution time, and no resolution method

#### Scenario: Resolved problem

- **WHEN** an admin resolves a problem payment
- **THEN** the record stores the resolving admin, the time, and the resolution method used

### Requirement: Only administrators reach the problem payments area

The problem payments list and every resolution action SHALL be restricted to authenticated administrators.
Customers MUST NOT be able to view or resolve problem payments.

#### Scenario: Customer attempts access

- **WHEN** a signed-in customer requests the problem payments list
- **THEN** access is refused

#### Scenario: Guest attempts access

- **WHEN** a signed-out visitor requests the problem payments list
- **THEN** they are sent to the login page

#### Scenario: Customer attempts a resolution

- **WHEN** a signed-in customer submits a manual payment resolution for a problem payment
- **THEN** access is refused and the problem payment stays unresolved

### Requirement: Administrators can review problem payments

The system SHALL show administrators a list of problem payments, newest first, defaulting to the
unresolved ones, and allowing filtering by resolution state and by reason category. Each entry SHALL show
the order number (when known), the reason, the amount, the time, and the resolution state. An
administrator SHALL be able to open one entry and see the full gateway payload and the order it relates
to.

#### Scenario: List defaults to unresolved

- **WHEN** an administrator opens the problem payments list
- **THEN** only unresolved problem payments are listed, newest first

#### Scenario: Filtering by reason

- **WHEN** an administrator filters the list by reason `declined`
- **THEN** only problem payments with that reason are listed

#### Scenario: Viewing one problem payment

- **WHEN** an administrator opens a problem payment recorded against an order
- **THEN** the raw gateway payload and a link to that order are shown

#### Scenario: Order detail links to its problems

- **WHEN** an administrator views an order that has unresolved problem payments
- **THEN** the order page shows that the order has problem payments and links to them

### Requirement: Administrators can re-check a problem payment with the gateway

For a problem payment carrying a transaction reference, an administrator SHALL be able to ask the gateway
again whether the transaction succeeded. When the gateway confirms a valid transaction whose amount and
currency match the order, the order SHALL be settled and the problem marked resolved by
`gateway_recheck`. When the gateway does not confirm it, nothing about the order SHALL change and the
problem SHALL stay unresolved, with the outcome reported to the administrator.

#### Scenario: Gateway now confirms the payment

- **WHEN** an administrator re-checks a problem payment and the gateway reports the transaction valid for
  the order full amount and currency
- **THEN** the order payment status becomes `paid`
- **AND** a successful payment attempt is recorded against the order
- **AND** the problem payment is resolved with method `gateway_recheck`

#### Scenario: Gateway still reports failure

- **WHEN** an administrator re-checks a problem payment and the gateway still reports the transaction
  invalid
- **THEN** the order is not settled
- **AND** the problem payment remains unresolved
- **AND** the administrator is told the gateway did not confirm the payment

#### Scenario: Gateway confirms a different amount

- **WHEN** the gateway confirms a transaction whose amount is less than the order grand total
- **THEN** the order is not settled
- **AND** the problem payment remains unresolved
- **AND** the administrator is told the confirmed amount does not match the order

#### Scenario: No transaction reference to re-check

- **WHEN** a problem payment was recorded with reason `initiation_error` and carries no transaction
  reference
- **THEN** the re-check action is not offered for that record

#### Scenario: Gateway unreachable during re-check

- **WHEN** the gateway cannot be reached while re-checking
- **THEN** the order is not settled, the problem payment remains unresolved, and the administrator is told
  the gateway could not be reached

### Requirement: Administrators can record a payment received out of band

An administrator SHALL be able to record that money for an order arrived outside the gateway, supplying a
reference and an optional note. Doing so SHALL settle the order and mark the problem resolved by `manual`.
The reference SHALL be required, so every manual settlement is traceable.

#### Scenario: Manual payment recorded

- **WHEN** an administrator records a manual payment for a problem payment, giving reference `BKH8821X`
  and a note
- **THEN** the order payment status becomes `paid` and its status becomes `paid`
- **AND** a payment attempt is recorded against the order for the order full amount, marked as manually
  settled rather than gateway settled, with the supplied reference
- **AND** the problem payment is resolved with method `manual`, storing the administrator, the time, the
  reference, and the note

#### Scenario: Reference omitted

- **WHEN** an administrator submits a manual payment with no reference
- **THEN** the submission is rejected with a validation error
- **AND** the order and the problem payment are unchanged

### Requirement: Manual settlement is distinguishable from gateway settlement

A manually settled order SHALL use the same `paid` payment status as a gateway-settled one, so nothing
downstream has to learn a new status. The manual origin SHALL remain recoverable from the payment record
and from the resolved problem payment, so reconciliation can tell the two apart.

#### Scenario: Reconciling settled orders

- **WHEN** reviewing an order settled by an administrator recording an out-of-band payment
- **THEN** its payment status reads `paid`
- **AND** the payment attempt shows it was settled manually, not by the gateway
- **AND** the resolved problem payment names the administrator who settled it

### Requirement: An order is never settled twice

A resolution action SHALL NOT settle an order that is already paid, and SHALL NOT re-resolve a problem
payment that is already resolved. Attempting either SHALL leave the order and the record unchanged and
report why.

#### Scenario: Order already paid

- **WHEN** an administrator records a manual payment for a problem payment whose order is already `paid`
- **THEN** no further payment attempt is recorded against the order
- **AND** the administrator is told the order is already paid

#### Scenario: Problem already resolved

- **WHEN** an administrator submits a resolution for a problem payment that is already resolved
- **THEN** the existing resolution is preserved unchanged
- **AND** the administrator is told the problem payment is already resolved

#### Scenario: Two administrators resolve at once

- **WHEN** two administrators submit resolutions for the same problem payment at the same time
- **THEN** exactly one resolution takes effect
- **AND** the order carries exactly one successful payment attempt from the resolution
