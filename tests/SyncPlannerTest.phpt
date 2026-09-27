<?php

declare(strict_types=1);

use Matodo\Auth\Facade\SyncPlanner;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$planner = new SyncPlanner();

// stav v DB
$current = [
    'news:view' => ['package' => 'matodo/news', 'active' => true],
    'news:edit' => ['package' => 'matodo/news', 'active' => true],
    'old:thing' => ['package' => 'matodo/old', 'active' => true],
    'gone:before' => ['package' => 'matodo/old', 'active' => false],
    'back:again' => ['package' => 'matodo/back', 'active' => false],
];

// co deklarují nainstalované balíčky
$provided = [
    'news:view' => ['package' => 'matodo/news', 'description' => 'Zobrazení novinek'],
    'news:edit' => ['package' => 'matodo/news', 'description' => 'Úprava novinek'],
    'news:publish' => ['package' => 'matodo/news', 'description' => 'Publikace novinek'],
    'back:again' => ['package' => 'matodo/back', 'description' => 'Vrácená akce'],
];

$plan = $planner->plan($current, $provided);

// nové oprávnění se přidá
Assert::same(['news:publish'], $plan->add);

// oprávnění, které už nikdo nedeklaruje, se deaktivuje (jen pokud je aktivní)
Assert::same(['old:thing'], $plan->deactivate);

// dříve deaktivované oprávnění, které se vrátilo, se znovu aktivuje
Assert::same(['back:again'], $plan->reactivate);

// žádné providery -> vše aktivní se deaktivuje, nic se nepřidá
$plan2 = $planner->plan($current, []);
Assert::same([], $plan2->add);
Assert::same(['news:view', 'news:edit', 'old:thing'], $plan2->deactivate);
Assert::same([], $plan2->reactivate);
