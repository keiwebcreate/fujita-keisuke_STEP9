<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PurchaseConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_independent_mysql_connections_cannot_buy_the_same_last_item(): void
    {
        $product = Product::factory()->create(['stock' => 1]);
        $buyers = User::factory()->count(2)->create();
        $code = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'testing' || config('database.connections.mysql.url')) {
    throw new RuntimeException('Only MySQL testing database is allowed');
}
Illuminate\Support\Facades\DB::statement('SET SESSION innodb_lock_wait_timeout = 8');
echo 'READY:'.Illuminate\Support\Facades\DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
flush();
try {
    App\Models\Sale::purchase((int) $argv[1], $argv[2], 1);
    echo json_encode(['status' => 201]).PHP_EOL;
} catch (Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
    echo json_encode(['status' => $e->getStatusCode()]).PHP_EOL;
}
PHP;
        $processes = [];
        DB::beginTransaction();
        try {
            Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            foreach ($buyers as $buyer) {
                $process = new Process([PHP_BINARY, '-r', $code, (string) $buyer->id, (string) $product->id], base_path(), [
                    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'testing', 'DB_URL' => '',
                ]);
                $process->setTimeout(15);
                $process->start();
                $processes[] = $process;
            }
            foreach ($processes as $process) {
                $this->assertTrue($process->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'READY:')), $process->getErrorOutput());
            }
            $connection_ids = array_map(function (Process $process): string {
                preg_match('/READY:(\d+)/', $process->getOutput(), $matches);

                return $matches[1];
            }, $processes);
            $this->assertNotSame($connection_ids[0], $connection_ids[1]);
            DB::commit();
            $statuses = [];
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $lines = explode("\n", trim($process->getOutput()));
                $statuses[] = json_decode(end($lines), true, 512, JSON_THROW_ON_ERROR)['status'];
            }
            sort($statuses);
            $this->assertSame([201, 409], $statuses);
            $this->assertSame(0, $product->fresh()->stock);
            $this->assertSame(1, (int) Sale::query()->where('product_id', $product->id)->sum('quantity'));
            $this->assertDatabaseCount('sales', 1);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
        }
    }
}
