<?php
declare(strict_types=1);

namespace Sirius\Orm\Tests\Action;

use Sirius\Orm\Action\ActionInterface;

class FakeThrowsException implements ActionInterface
{
    public function run() {
        throw new \Exception();
    }

    public function revert() {
    }

    public function onSuccess() {}
}