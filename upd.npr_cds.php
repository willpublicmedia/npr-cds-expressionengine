<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/libraries/installation/dependency_manager.php';
require_once __DIR__ . '/database/migrations/story_api/story_api_settings_migrator.php';
require_once __DIR__ . '/database/migrations/story_api/field_group_migrator.php';
require_once __DIR__ . '/database/installation/fields/field_installer.php';
require_once __DIR__ . '/database/installation/fields/field_conditioner.php';
require_once __DIR__ . '/database/installation/channel_installer.php';
require_once __DIR__ . '/database/installation/status_installer.php';
require_once __DIR__ . '/database/migrations/story_api/legacy_extension_installer.php';
require_once __DIR__ . '/database/installation/tables/table_loader.php';
require_once __DIR__ . '/database/installation/tables/itable.php';
require_once __DIR__ . '/libraries/installation/table_installer.php';
require_once __DIR__ . '/Model/DefaultNprStoryLayout.php';
require_once __DIR__ . '/database/installation/updates/updater_0_3_0.php';
require_once __DIR__ . '/database/installation/updates/updater_0_3_1.php';
require_once __DIR__ . '/database/installation/updates/updater_0_4_0.php';

use ExpressionEngine\Service\Addon\Installer;
use IllinoisPublicMedia\NprCds\Database\Installation\Channel_installer;
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Field_conditioner;
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Field_installer;
use IllinoisPublicMedia\NprCds\Database\Installation\Layout_customizer;
use IllinoisPublicMedia\NprCds\Database\Installation\Status_installer;
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\ITable;
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\Table_loader;
use IllinoisPublicMedia\NprCds\Database\Installation\Updates\Updater_0_3_0;
use IllinoisPublicMedia\NprCds\Database\Installation\Updates\Updater_0_3_1;
use IllinoisPublicMedia\NprCds\Database\Installation\Updates\Updater_0_4_0;
use IllinoisPublicMedia\NprCds\Database\Migrations\StoryApi\Field_group_migrator;
use IllinoisPublicMedia\NprCds\Database\Migrations\StoryApi\Legacy_extension_installer;
use IllinoisPublicMedia\NprCds\Database\Migrations\StoryApi\Story_api_settings_migrator;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Dependency_manager;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Table_installer;
use IllinoisPublicMedia\NprCds\Model\DefaultNprStoryLayout;

class Npr_cds_upd extends Installer
{
    public $has_cp_backend = 'y';

    public $has_publish_fields = 'n';

    private $channels = [
        'npr_stories',
    ];

    private $publish_layout = DefaultNprStoryLayout::LAYOUT_NAME;

    private $tables = [
        // table order matters for column relationships
        'config' => [
            'config_settings',
            // 'config_field_mappings',
        ],
        'story' => [
            //     'pushed_stories',
        ],
    ];

    /**
     * NPR CDS updater constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        ee()->load->dbforge();
    }

    /**
     * Install NPR CDS module.
     *
     * @return bool
     */
    public function install()
    {
        if ($this->check_dependencies() === false) {
            return false;
        }

        // create and/or migrate settings
        $this->create_tables($this->tables['config']);

        $this->create_required_fields();
        $this->condition_fields();
        $this->create_required_statuses();
        $this->create_required_channels();

        if ($this->npr_story_api_installed() === true) {
            $this->migrate_story_api_settings();

            // to do: migrate story api fields

            $this->migrate_mapped_channel_field_groups();

            // to do: apply layouts to mapped channels
            $this->update_publish_form_layouts();

            $this->delete_legacy_extensions();
        }

        parent::install();

        return true;
    }

    /**
     * Uninstall NPR CDS module.
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->delete_tables($this->tables['config']);

        parent::uninstall();

        return true;
    }

    /**
     * Update NPR CDS module.
     *
     * @param  mixed $current Current module version.
     *
     * @return bool
     */
    public function update($current = '')
    {
        if ($this->check_dependencies() === false) {
            return false;
        }

        if ($current < '0.1.0') {
            $this->condition_fields();
        }

        if ($current < '0.3.0') {
            $this->update_0_3_0();
        }

        if ($current < '0.3.1') {
            $this->update_0_3_1();
        }

        if ($current < '0.4.0') {
            $this->update_0_4_0();
        }

        parent::update($current);

        return true;
    }

    private function check_dependencies(): bool
    {
        $manager = new Dependency_manager();
        $has_dependencies = $manager->check_dependencies();

        return $has_dependencies;
    }

    private function condition_fields(): void
    {
        $conditioner = new Field_conditioner();
        $conditioner->condition_fields();
    }

    private function migrate_mapped_channel_field_groups(): void
    {
        $migrator = new Field_group_migrator();
        $migrator->migrate();
    }

    private function migrate_story_api_settings(): void
    {
        $migrator = new Story_api_settings_migrator();
        $migrator->migrate();
    }

    private function npr_story_api_installed(): bool
    {
        $info = ee('Addon')->get('npr_story_api');
        $installed = $info->isInstalled();
        return $installed;
    }

    private function create_required_channels()
    {
        $installer = new Channel_installer();
        $installer->install($this->channels, $this->publish_layout);
    }

    private function create_required_fields()
    {
        $installer = new Field_installer();
        $installer->install();
    }

    private function create_required_statuses()
    {
        $statuses = [
            'draft',
        ];

        $installer = new Status_installer();
        $installer->install($statuses);
    }

    private function create_tables(array $table_names)
    {
        $tables = [];
        foreach ($table_names as $name) {
            $data = $this->load_table_config($name);
            array_push($tables, $data);
        }

        $installer = new Table_installer();
        $installer->install($tables);
    }

    private function delete_legacy_extensions()
    {
        $uninstaller = new Legacy_extension_installer();
        $uninstaller->uninstall();
    }

    private function delete_tables(array $table_names)
    {
        $tables = [];
        foreach ($table_names as $name) {
            $data = $this->load_table_config($name);
            $table_name = $data->table_name();
            array_push($tables, $table_name);
        }

        $uninstaller = new Table_installer();
        $uninstaller->uninstall($tables);
    }

    private function load_table_config(string $table_name): ITable
    {
        $loader = new Table_loader();
        $data = $loader->load($table_name);

        return $data;
    }

    private function update_0_3_0(): void
    {
        $updater = new Updater_0_3_0();
        $updater->update();
    }

    private function update_0_3_1(): void
    {
        $updater = new Updater_0_3_1();
        $updater->update();
    }

    private function update_0_4_0(): void
    {
        $updater = new Updater_0_4_0();
        $updater->update();
    }

    private function update_publish_form_layouts(): void
    {
        $settings = ee()->db->select('mapped_channels')
            ->limit(1)
            ->get('npr_cds_settings')
            ->result_array();

        $channel_ids = explode('|', $settings[0]['mapped_channels']);
        foreach ($channel_ids as $channel_id) {
            $channel = ee('Model')->get('Channel')->filter('channel_id', $channel_id)->first();
            if ($channel == null) {
                continue;
            }

            $customizer = new Layout_customizer($channel);
            $customizer->install(DefaultNprStoryLayout::LAYOUT_NAME);
        }
    }
}
