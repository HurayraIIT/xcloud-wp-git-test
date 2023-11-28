<?php

namespace WPSP_PRO;

class Scheduled
{
    public function __construct()
    {
        $this->load_schedule_published();
        $this->load_manage_schedule();
        $this->load_missed_schedule();
        add_filter('wpsp_social_profile_limit_checkpoint', array($this, 'allow_multi_social_sharing'));
    }


    public function load_schedule_published()
    {
        new Scheduled\Published();
        new Scheduled\PublishedElementor();
    }

    public function load_manage_schedule()
    {
        new Scheduled\Manage();
    }

    public function load_missed_schedule()
    {
        new Scheduled\Missed();
    }
    public function allow_multi_social_sharing($profile)
    {
        if (class_exists('WPSP_PRO')) {
            return true;
        }
        return false;
    }
}
