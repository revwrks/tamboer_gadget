<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddTimestampsToTables extends Command
{
    protected $signature = 'add:timestamps';

    protected $description = 'Add timestamps to all tables that do not have created_at and updated_at columns';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $tables = DB::select('SHOW TABLES');
        $database = env('DB_DATABASE');

        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_$database"};

            // Skip tables that already have timestamps
            if (!Schema::hasColumn($tableName, 'created_at') && !Schema::hasColumn($tableName, 'updated_at')) {

                // Add a unique identifier (timestamp) to the class name to avoid conflicts
                $timestamp = time() . rand(1000, 9999); // Generate unique identifier for class name
                $className = 'AddTimestampsTo' . Str::studly($tableName) . 'Table_' . $timestamp; // Make it unique
                $migrationName = 'add_timestamps_to_' . $tableName . '_table';
                $fileName = date('Y_m_d_His') . '_' . $migrationName . '.php';
                $path = database_path('migrations/' . $fileName);

                // Migration file content
                $migrationContent = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class {$className} extends Migration
{
    public function up()
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            \$table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            \$table->dropTimestamps();
        });
    }
}
EOT;

                // Write the migration file
                file_put_contents($path, $migrationContent);
                $this->info("Created migration for table $tableName.");
            }
        }

        $this->info('Timestamp migrations generated successfully.');
    }
}
