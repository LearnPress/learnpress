<?php
/**
 * Template for displaying profile header.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

/**
 * @var LP_User $user
 */

$profile = LP_Profile::instance();
$user    = $profile->get_user();

if ( ! isset( $user ) ) {
	return;
}

?>

<header id="profile-header" class="lp-content-area">
    <div class="lp-profile-header__inner">
        <div class="lp-profile-username">
			<?php echo $user->get_display_name(); ?>
        </div>
		<?php if ( $bio = $user->get_description() ) { ?>
            <div class="lp-profile-user-bio">
				<?php echo $bio; ?>
            </div>
		<?php } ?>
    </div>
</header>