## Purpose

Lets a customer pay for an order through the SSLCommerz hosted payment page, and defines what the store
must verify before it accepts that an order has been paid for.

## ADDED Requirements

### Requirement: SSLCommerz is offered only when fully configured

The system SHALL offer SSLCommerz at checkout only when the gateway is enabled and both a store id and a
store password are present. An unconfigured gateway MUST NOT appear as a payment choice, and MUST be
rejected if submitted anyway.

#### Scenario: Gateway configured

- **WHEN** SSLCommerz is enabled and its store id and store password are set
- **THEN** "SSLCommerz" is listed as a payment method on the checkout page

#### Scenario: Credentials missing

- **WHEN** SSLCommerz is enabled but the store password is blank
- **THEN** SSLCommerz is absent from the checkout page payment methods
- **AND** a request that submits `sslcommerz` as the payment method is rejected with a validation error

### Requirement: Customer is redirected to the hosted payment page

When a customer places an order choosing SSLCommerz, the system SHALL request a payment session from
SSLCommerz for the order grand total and currency, and redirect the customer browser to the payment page
the gateway returns. The order SHALL be recorded as placed with payment status `pending` before the
redirect, so no purchase is lost if the customer never returns.

#### Scenario: Session created

- **WHEN** a customer places an order for 4,250.00 BDT paying by SSLCommerz and the gateway returns a
  payment page URL
- **THEN** the order exists with status `pending` and payment status `pending`
- **AND** stock for the ordered items is already reserved
- **AND** a pending payment attempt is recorded carrying the transaction reference sent to the gateway
- **AND** the customer browser is redirected to the payment page URL returned by the gateway

#### Scenario: Transaction reference is unique per attempt

- **WHEN** two payment attempts are started for the same order
- **THEN** each attempt sends a distinct transaction reference to the gateway
- **AND** each reference identifies exactly one order when a callback arrives

### Requirement: Amount and currency are derived from the order

The amount and currency sent to the gateway SHALL be taken from the stored order, never from the request
that started the payment. A payment request MUST NOT be able to change what the customer is charged.

#### Scenario: Client-supplied amount is ignored

- **WHEN** a payment is started for an order whose grand total is 4,250.00 BDT and the request also
  carries an `amount` field of 1.00
- **THEN** the session request sent to the gateway is for 4,250.00 BDT

### Requirement: Settlement is decided by the gateway notification, not the browser

The system SHALL treat the server-to-server payment notification (IPN) as the only authority for marking
an order paid. Returning to the success URL in a browser MUST NOT by itself settle an order.

#### Scenario: Customer returns before the notification arrives

- **WHEN** a customer browser reaches the success URL for an order whose notification has not yet been
  received and validated
- **THEN** the order payment status remains `pending`
- **AND** the customer is shown their order with a message that payment is being confirmed

#### Scenario: Forged success return

- **WHEN** a request is made directly to the success URL carrying a transaction reference that the gateway
  never confirmed
- **THEN** the order is not marked paid
- **AND** no payment attempt is recorded as successful

#### Scenario: Notification settles the order

- **WHEN** a gateway notification for an order is received and passes validation
- **THEN** the order payment status becomes `paid` and its status becomes `paid`
- **AND** a successful payment attempt is recorded with the gateway reference and the time of payment

### Requirement: Every callback is validated against the gateway before it is trusted

Before acting on any callback, the system SHALL confirm with SSLCommerz that the transaction is valid, and
SHALL confirm that the transaction amount and currency match the order own grand total and currency. A
callback that fails any of these checks MUST NOT settle the order.

#### Scenario: Validation confirms the transaction

- **WHEN** a callback arrives and the gateway validation response reports the transaction valid for
  4,250.00 BDT against an order whose grand total is 4,250.00 BDT
- **THEN** the order is settled

#### Scenario: Amount does not match the order

- **WHEN** a callback arrives whose validated amount is 1.00 BDT for an order whose grand total is
  4,250.00 BDT
- **THEN** the order is not settled
- **AND** the mismatch is recorded as a problem payment

#### Scenario: Currency does not match the order

- **WHEN** a callback arrives whose validated currency differs from the order currency
- **THEN** the order is not settled
- **AND** the mismatch is recorded as a problem payment

#### Scenario: Gateway reports the transaction invalid

- **WHEN** a callback arrives and the gateway validation response reports the transaction invalid or
  unknown
- **THEN** the order is not settled
- **AND** the failure is recorded as a problem payment

#### Scenario: Callback names an unknown order

- **WHEN** a callback arrives carrying a transaction reference that matches no order
- **THEN** the request is rejected
- **AND** the event is recorded as a problem payment with no order attached

### Requirement: Notifications are safe to receive more than once

The gateway may deliver the same notification repeatedly. The system SHALL settle an order at most once:
re-delivery of a notification for an already-paid order MUST leave the order and its payment record
unchanged, and MUST be acknowledged rather than treated as an error.

#### Scenario: Duplicate notification

- **WHEN** a notification is received for an order that is already `paid` from the same transaction
- **THEN** the order keeps one successful payment record with its original payment time
- **AND** the response acknowledges the notification successfully
- **AND** no problem payment is recorded

### Requirement: The notification endpoint is publicly reachable and unauthenticated

The notification endpoint SHALL accept requests that carry no user session and no CSRF token, since the
gateway calls it server-to-server. It SHALL be rate limited, and it SHALL rely on gateway-side validation
rather than on the identity of the caller.

#### Scenario: Notification without a session

- **WHEN** the gateway posts a notification with no cookies and no CSRF token
- **THEN** the request is accepted and processed

#### Scenario: Flooded endpoint

- **WHEN** the notification endpoint receives requests beyond its rate limit from one source
- **THEN** further requests from that source are rejected with HTTP 429 until the window passes

### Requirement: A cancelled payment leaves the order recoverable

When the customer abandons or cancels the hosted payment page, the system SHALL leave the order in place
with payment status `pending` and return the customer to their order with an explanation of how to pay.
A cancellation MUST NOT be recorded as a problem payment.

#### Scenario: Customer cancels on the payment page

- **WHEN** a customer clicks cancel on the gateway payment page and is returned to the cancel URL
- **THEN** the order still exists with payment status `pending`
- **AND** the customer sees a message that payment was not completed
- **AND** no problem payment row is created

### Requirement: A failed payment leaves the order unpaid and recorded

When the gateway reports that a transaction failed, the system SHALL leave the order unpaid, record the
failure so it can be followed up, and tell the customer the payment did not go through.

#### Scenario: Card declined

- **WHEN** the gateway returns the customer to the failure URL and reports the transaction failed
- **THEN** the order payment status remains `pending`
- **AND** the failure is recorded as a problem payment against that order
- **AND** the customer is shown that the payment did not complete

### Requirement: Sandbox and live gateway hosts are selected explicitly

The system SHALL direct all gateway traffic to the sandbox host when sandbox mode is on and to the live
host when it is off, for session creation, validation, and transaction queries alike.

#### Scenario: Sandbox mode

- **WHEN** sandbox mode is enabled
- **THEN** session, validation, and transaction-query requests all go to the sandbox host

#### Scenario: Live mode

- **WHEN** sandbox mode is disabled
- **THEN** session, validation, and transaction-query requests all go to the live host
