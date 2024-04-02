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

        $channel->ChannelLayouts->add($layout);
        $layout->synchronize($channel->getAllCustomFields());

        $channel->save();
        $layout->save();
    }

    private function create_layout($layout_name)
    {
        $model = ee('Model')->get('ChannelLayout')->filter('layout_name', '==', $layout_name)->first();

        if ($model != null) {
            return;
        }

        $channel_layout = ee('Model')->make('ChannelLayout');
        $channel_layout->Channel = $this->channel;

        $default_layout = new DefaultNprStoryLayout($this->channel->channel_id, null);
        $field_layout = $default_layout->getLayout();

        $channel_layout->layout_name = $layout_name;
        $channel_layout->field_layout = $field_layout;

        if (APP_VER < 6) {
            $member_groups = ee('Model')->get('MemberGroup')
                ->filter('group_title', 'NOT IN', $this->member_group_deny)
                ->all();

            $channel_layout->MemberGroups = $member_groups;
        } else {
            $member_roles = ee('Model')->get('Role')
                ->filter('name', 'NOT IN', $this->member_group_deny)
                ->all();

            $channel_layout->PrimaryRoles = $member_roles;
        }

        $channel_layout->save();
    }
}