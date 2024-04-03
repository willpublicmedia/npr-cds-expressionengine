<?php

namespace IllinoisPublicMedia\NprCds\Database\Installation;

if (!defined('BASEPATH')) {
    exit('No direct script access.');
}

require_once __DIR__ . '/../../Model/DefaultNprStoryLayout.php';
use ExpressionEngine\Model\Channel\Channel;
use IllinoisPublicMedia\NprCds\Model\DefaultNprStoryLayout;

class Layout_customizer
{
    private $channel;

    private $member_group_deny = [
        'Banned',
        'Guests',
        'Pending',
    ];

    public function __construct($channel)
    {
        $this->channel = $channel;
    }

    public function install($layout_name)
    {
        $this->create_layout($layout_name);
        $this->assign_layout($layout_name, $this->channel);
    }

    public function uninstall($layout_name)
    {
        $model = ee('Model')->get('ChannelLayout')->filter('layout_name', '==', $layout_name)->first();

        if ($model != null) {
            $model->delete();
        }
    }

    private function assign_layout($layout_name, $channel)
    {
        $layout = ee('Model')->get('ChannelLayout')->filter('layout_name', '==', $layout_name)->first();

        // get channel assigned member groups and layouts
        $assigned_roles = $channel->AssignedRoles;
        $old_layouts = $channel->ChannelLayouts->pluck('layout_id');

        // unassign old member layout assignments
        // do NOT delete old layouts
        ee()->db->where_in('layout_id', $old_layouts)->delete('layout_publish_member_roles');

        //assign new layout
        $layout->PrimaryRoles = $assigned_roles;
        $channel->ChannelLayouts->add($layout);
        $layout->synchronize($channel->getAllCustomFields());

        $channel->save();
        $layout->save();
    }

    private function create_layout($layout_name)
    {
        $model = ee('Model')->get('ChannelLayout')->filter('layout_name', '==', $layout_name)->first();

        if ($model != null && $model->channel_id == $this->channel->channel_id) {
            return;
        }

        $channel_layout = ee('Model')->make('ChannelLayout');
        $channel_layout->Channel = $this->channel;

        $default_layout = new DefaultNprStoryLayout($this->channel->channel_id, null);
        $field_layout = $default_layout->getLayout();

        $channel_layout->layout_name = $layout_name;
        $channel_layout->field_layout = $field_layout;

        $channel_layout->save();
    }
}
