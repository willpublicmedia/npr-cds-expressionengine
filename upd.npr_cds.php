<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/libraries/installation/dependency_manager.php';
// require_once __DIR__ . '/libraries/installation/field_installer.php';
// require_once __DIR__ . '/libraries/installation/channel_installer.php';
// require_once __DIR__ . '/libraries/installation/status_installer.php';
// require_once __DIR__ . '/libraries/installation/extension_installer.php';
require_once __DIR__ . '/libraries/configuration/tables/table_loader.php';
require_once __DIR__ . '/libraries/configuration/tables/itable.php';
require_once __DIR__ . '/libraries/installation/table_installer.php';
use IllinoisPublicMedia\NprCds\Constants;
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Tables\ITable;
use IllinoisPublicMedia\NprCds\Libraries\Configuration\Tables\Table_loader;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Dependency_manager;
// use IllinoisPublicMedia\NprStoryApi\Libraries\Installation\Channel_installer;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Table_installer;

// use IllinoisPublicMedia\NprStoryApi\Libraries\Installation\Extension_installer;
// use IllinoisPublicMedia\NprStoryApi\Libraries\Installation\Field_installer;
// use IllinoisPublicMedia\NprStoryApi\Libraries\Installation\Status_installer;

/**
 * NPR CDS updater.
 */
class Npr_cds_upd
{
    private $channels = array(
        'npr_stories',
    );

    private $module_name = 'Npr_cds';

    private $publish_layout = 'NPR CDS';

    private $tables = array(
        // table order matters for column relationships
        'config' => array(
            'config_settings',
            // 'config_field_mappings',
        ),
        // 'story' => array(
        //     'npr_story',
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
        // ),
    );

    private $version = Constants::VERSION;

    /**
     * NPR CDS updater constructor.
     *
     * @return void
     */
    public function __construct()
    {
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

        if ($this->npr_story_api_installed() === true) {
            $this->migrate_story_api_settings();
        }

        // $this->create_tables($this->tables['story']);
        // $this->create_required_fields();
        // $this->create_required_statuses();
        // $this->create_required_channels();
        // $this->create_required_extensions();

        $data = array(
            'module_name' => $this->module_name,
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n',
        );

        ee()->db->insert('modules', $data);

        return true;
    }

    /**
     * Uninstall NPR CDS module.
     *
     * @return bool
     */
    public function uninstall()
    {
        ee()->db->select('module_id');
        ee()->db->from('modules');
        ee()->db->where('module_name', $this->module_name);
        $query = ee()->db->get();

        ee()->db->delete('module_member_roles', array('module_id' => $query->row('module_id')));

        ee()->db->delete('modules', array('module_name' => $this->module_name));
        ee()->db->delete('actions', array('class' => $this->module_name));

        // $this->delete_channels();
        // $this->delete_statuses();
        // $this->delete_fields();
        // $this->delete_extensions();
        // $this->delete_tables($this->tables['story']);
        // $this->delete_tables($this->tables['config']);

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
        if (version_compare($this->version, '1.0.0', '<')) {
            $this->uninstall();
            $this->install();

            return true;
        }

        if (version_compare($current, $this->version, '=')) {
            return false;
        }

        if ($this->check_dependencies() === false) {
            return false;
        }

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
        $legacy_settings = ee()->db->get('npr_story_api_settings')->result_array();

        if (count($legacy_settings) <= 0) {
            return;
        }

        $legacy_settings = $legacy_settings[0];
        $data = array(
            'mapped_channels' => $legacy_settings['mapped_channels'],
            'npr_image_destination' => $legacy_settings['npr_image_destination'],
            'org_id' => $legacy_settings['org_id'],
        );

        ee()->db->where('id', 1);
        ee()->db->update('npr_cds_settings', $data);

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

    // private function delete_extensions()
    // {
    //     $uninstaller = new Extension_installer();
    //     $uninstaller->uninstall();
    // }

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
