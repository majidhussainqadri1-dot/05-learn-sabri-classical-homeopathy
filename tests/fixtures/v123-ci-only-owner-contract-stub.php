<?php
/**
 * CI-ONLY CANONICAL CONTRACT STUB — never package or install on a real site.
 * Fakes File00/01/20 so File05 can run against disposable genuine WP+MariaDB.
 * This does NOT replace real companion plugin compatibility/staging acceptance.
 */
defined('ABSPATH') || exit;
if (!defined('SMC_VERSION')) define('SMC_VERSION','ci-stub');
if (!defined('SMC_CONTRACT_VERSION')) define('SMC_CONTRACT_VERSION','1.2.3');
if (!defined('SPF_VERSION')) define('SPF_VERSION','ci-stub');
if (!defined('SABRI_SHELL_VERSION')) define('SABRI_SHELL_VERSION','ci-stub');
if (!class_exists('SMC_Contracts')) {
    class SMC_Contracts {
        public static function assertions($user_id=0) {
            $user_id=(int)$user_id;
            return array(
                'contract_version'=>'1.2.3','user_id'=>$user_id,'status'=>'approved',
                'membership_type'=>'member','account_class'=>'member',
                'approved'=>$user_id>0,'eligible'=>$user_id>0,
                'suspended'=>false,'guardian_verified'=>$user_id>0,
                'identity_documents_current'=>$user_id>0,
                'email_verified'=>$user_id>0,'phone_verified'=>$user_id>0,
                'professional_verified'=>false,'approved_membership_types'=>array('member'),
                'publishing'=>array(), 'entitlements'=>array('education'=>true)
            );
        }
    }
}
if (!function_exists('smc_policy')) {
    function smc_policy() {
        return array(
            'single_free_tier'=>true,'paid_unlocks_enabled'=>false,'legacy_pricing_enabled'=>false,
            'donation_optional'=>true,'free_baseline'=>true,
            'base_services'=>array('education','ai'),
            'donation_affects_rank'=>false,'donation_affects_entitlement'=>false,
            'donation_affects_capability'=>false,'donation_affects_visibility'=>false,
            'donation_affects_support'=>false,
            'commission_percent'=>0,
            'brand_primary'=>'#087A4E','numbered_file_max'=>26,
            'search_discovery_owner'=>'file26',
        );
    }
}
if (!function_exists('smc_is_founder')) {
    function smc_is_founder($user_id=0) {
        return (int)$user_id===1;
    }
}
