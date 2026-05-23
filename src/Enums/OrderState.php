<?php

namespace Rilong\MonobankInstallments\Enums;

enum OrderState: string
{
    case Success = 'SUCCESS';
    case Fail = 'FAIL';
    case InProcess = 'IN_PROCESS';
}
