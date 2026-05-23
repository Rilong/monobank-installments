<?php

use Rilong\MonobankInstallments\DTOs\ReturnAdditionalParamsDTO;

it('ReturnAdditionalParamsDTO toArray includes nds when set', function () {
    $dto = new ReturnAdditionalParamsDTO(nds: 208.42);
    expect($dto->toArray())->toBe(['nds' => 208.42]);
});

it('ReturnAdditionalParamsDTO toArray returns empty array when all null', function () {
    $dto = new ReturnAdditionalParamsDTO();
    expect($dto->toArray())->toBe([]);
});
