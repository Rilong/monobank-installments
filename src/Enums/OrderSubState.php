<?php

namespace Rilong\MonobankInstallments\Enums;

enum OrderSubState: string
{
    case Active = 'ACTIVE';
    case Done = 'DONE';
    case Returned = 'RETURNED';
    case WaitingForClient = 'WAITING_FOR_CLIENT';
    case ClientNotFound = 'CLIENT_NOT_FOUND';
    case ExceededSumLimit = 'EXCEEDED_SUM_LIMIT';
    case PayPartsAreNotAcceptable = 'PAY_PARTS_ARE_NOT_ACCEPTABLE';
    case ExistsOtherOpenOrder = 'EXISTS_OTHER_OPEN_ORDER';
    case NotEnoughMoneyForInitDebit = 'NOT_ENOUGH_MONEY_FOR_INIT_DEBIT';
    case ClientPushTimeout = 'CLIENT_PUSH_TIMEOUT';
    case FraudRejected = 'FRAUD_REJECTED';
    case RejectedByClient = 'REJECTED_BY_CLIENT';
    case WaitingForStoreConfirm = 'WAITING_FOR_STORE_CONFIRM';
    case RejectedByStore = 'REJECTED_BY_STORE';
    case Fail = 'FAIL';
    case RestrictedByRisks = 'RESTRICTED_BY_RISKS';
}
