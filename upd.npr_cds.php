<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/libraries/installation/dependency_manager.php';
require_once __DIR__ . '/database/migrations/pre_install/story_api_settings_migrator.php';
require_once __DIR__ . '/database/migrations/pre_install/story_api_content_migrator.php';
// require_once __DIR__ . '/libraries/installation/field_installer.php';
// require_once __DIR__ . '/libraries/installation/channel_installer.php';
// require_once __DIR__ . '/libraries/installation/status_installer.php';
require_once __DIR__ . '/libraries/installation/extension_installer.php';
require_once __DIR__ . '/libraries/configuration/tables/table_loader.php';
require_once __DIR__ . '/libraries/configuration/tables/itable.php';
require_once __DIR__ . '/libraries/installation/table_installer.php';

use ExpressionEngine\Service\Addon\Installer;
use IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall\Story_api_content_migrator;
use IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall\Story_api_settings_migrator;
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Tables\ITable;
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Tables\Table_loader;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Dependency_manager;
// use IllinoisPublicMedia\NprStoryApi\Libraries\Installation\Channel_installer;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Extension_installer;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Table_installer;

// use IllinoisPublicMedia\NprStoryApi\Libraries\Installation\Field_installer;
// use IllinoisPublicMedia\NprStoryApi\Libraries\Installation\Status_installer;

/**
 * NPR CDS updater.
 */
class Npr_cds_upd extends Installer
{
    public $has_cp_backend = 'y';

    public $has_publish_fields = 'n';

    private $channels = array(
        'npr_stories',
    );

    private $publish_layout = 'NPR CDS';

    private $tables = array(
        // table order matters for column relationships
        'config' => array(
            'config_settings',
            // 'config_field_mappings',
        ),
        'story' => array(
            'npr_story',
            //     'npr_organization',
            //     'npr_audio',
            //     'npr_audio_format',
            //     'npr_byline',
            //     'npr_html_asset',
            //     'npr_image',
            //     'npr_image_crop',
            //     'npr_permalink',
            //     'npr_pull_correction',
            //     'npr_pull_quote',
            //     // rewrite related link for push-only.
            //     // 'npr_related_link',
            //     'npr_text_paragraph',
            //     'npr_thumbnail',
            //     'pushed_stories',
        ),
    );

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
        $this->create_tables($this->tables['story']);

        if ($this->npr_story_api_installed() === true) {
            $this->migrate_story_api_settings();
            $this->migrate_story_api_content();

            // to do: migrate story api fields

            $legacy_extensions = true;
            $this->delete_extensions($legacy_extensions);
        }

        // $this->create_required_fields();
        // $this->create_required_statuses();
        // $this->create_required_channels();
        // $this->create_required_extensions();

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
        // $this->delete_channels();
        // $this->delete_statuses();
        // $this->delete_fields();
        // $this->delete_extensions();
        // $this->delete_tables($this->tables['story']);
        // $this->delete_tables($this->tables['config']);

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

        parent::update($current);

        return true;
    }

    private function check_dependencies(): bool
    {
        $manager = new Dependency_manager();
        $has_dependencies = $manager->check_dependencies();

        return $has_dependencies;
    }

    private function migrate_story_api_settings(): void
    {
        $migrator = new Story_api_settings_migrator();
        $migrator->migrate();
    }

    private function migrate_story_api_content(): void
    {
        $migrator = new Story_api_content_migrator();
        $migrator->migrate();

        return;
    }

    private function npr_story_api_installed(): bool
    {
        $info = ee('Addon')->get('npr_story_api');
        $installed = $info->isInstalled();
        return $installed;
    }

    // private function create_required_channels()
    // {
    //     $installer = new Channel_installer();
    //     $installer->install($this->channels, $this->publish_layout);
    // }

    // private function create_required_extensions()
    // {
    //     $installer = new Extension_installer();
    //     $installer->install();
    // }

    // private function create_required_fields()
    // {
    //     $installer = new Field_installer();
    //     $installer->install();
    // }

    // private function create_required_statuses()
    // {
    //     $statuses = array(
    //         'draft',
    //     );

    //     $installer = new Status_installer();
    //     $installer->install($statuses);
    // }

    private function create_tables(array $table_names)
    {
        $tables = array();
        foreach ($table_names as $name) {
            $data = $this->load_table_config($name);
            array_push($tables, $data);
        }

        $installer = new Table_installer();
        $installer->install($tables);
    }

    // private function delete_channels()
    // {
    //     $installer = new Channel_installer();
    //     $installer->uninstall($this->channels, $this->publish_layout);
    // }

    private function delete_extensions()
    {
        $uninstaller = new Extension_installer();
        $uninstaller->uninstall();
    }

    // private function delete_fields()
    // {
    //     $uninstaller = new Field_installer();
    //     $uninstaller->uninstall();
    // }

    // private function delete_statuses()
    // {
    //     $uninstaller = new Status_installer();
    //     $uninstaller->uninstall();
    // }

    // private function delete_tables(array $table_names)
    // {
    //     $tables = array();
    //     foreach ($table_names as $name) {
    //         $data = $this->load_table_config($name);
    //         $table_name = $data->table_name();
    //         array_push($tables, $table_name);
    //     }

    //     $uninstaller = new Table_installer();
    //     $uninstaller->uninstall($tables);
    // }

    private function load_table_config(string $table_name): ITable
    {
        $loader = new Table_loader();
        $data = $loader->load($table_name);

        return $data;
    }
}
