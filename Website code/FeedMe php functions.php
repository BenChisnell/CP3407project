<?php
/*
Plugin Name: FeedMe Portal
Description: FeedMe login, account, orders, address handling, and restaurant order integration.
Version: 1.0
*/

if (!defined('ABSPATH')) exit;

/* =========================================================
   FEEDME EXTERNAL DATABASE CONFIG
========================================================= */
define('FEEDME_DB_HOST', 'your-rds-endpoint.amazonaws.com');
define('FEEDME_DB_NAME', 'feedme');
define('FEEDME_DB_USER', 'your_db_username');
define('FEEDME_DB_PASS', 'your_db_password');
define('FEEDME_DB_CHARSET', 'utf8mb4');

/* =========================================================
   FEEDME TABLE NAMES
========================================================= */
define('FEEDME_TABLE_USER', 'User');
define('FEEDME_TABLE_USER_ADDRESS', 'User Address');
define('FEEDME_TABLE_ADDRESS', 'Address');
define('FEEDME_TABLE_RESTAURANTS', 'Restaurants');
define('FEEDME_TABLE_MENU', 'Menu');
define('FEEDME_TABLE_CART', 'Cart');
define('FEEDME_TABLE_CART_ITEMS', 'Cart Items');
define('FEEDME_TABLE_ORDERS', 'Orders');
define('FEEDME_TABLE_ORDER_ITEMS', 'Order Items');
define('FEEDME_TABLE_PROMO_CODE', 'Promo Code');

/* =========================================================
   SESSION
========================================================= */
add_action('init', function () {
    if (!session_id()) {
        session_start();
    }
}, 1);

/* =========================================================
   DATABASE CONNECTION
========================================================= */
function feedme_db() {
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    require_once ABSPATH . WPINC . '/wp-db.php';
    $db = new wpdb(FEEDME_DB_USER, FEEDME_DB_PASS, FEEDME_DB_NAME, FEEDME_DB_HOST);
    $db->query("SET NAMES '" . esc_sql(FEEDME_DB_CHARSET) . "'");
    $db->show_errors(false);

    return $db;
}

function feedme_t($name) {
    return '`' . str_replace('`', '', $name) . '`';
}

/* =========================================================
   SESSION / USER HELPERS
========================================================= */
function feedme_is_logged_in() {
    return !empty($_SESSION['feedme_user_id']);
}

function feedme_current_user_id() {
    return !empty($_SESSION['feedme_user_id']) ? intval($_SESSION['feedme_user_id']) : 0;
}

function feedme_login_user($user) {
    $_SESSION['feedme_user_id'] = intval($user->user_id);
    $_SESSION['feedme_user_email'] = (string)$user->email;
    $_SESSION['feedme_user_name'] = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
}

function feedme_logout_user() {
    unset($_SESSION['feedme_user_id'], $_SESSION['feedme_user_email'], $_SESSION['feedme_user_name']);
}

function feedme_get_user_by_id($user_id) {
    $db = feedme_db();
    $table = feedme_t(FEEDME_TABLE_USER);

    return $db->get_row(
        $db->prepare("SELECT * FROM {$table} WHERE user_id = %d LIMIT 1", $user_id)
    );
}

function feedme_get_user_by_email($email) {
    $db = feedme_db();
    $table = feedme_t(FEEDME_TABLE_USER);

    return $db->get_row(
        $db->prepare("SELECT * FROM {$table} WHERE email = %s LIMIT 1", $email)
    );
}

function feedme_get_current_user_profile() {
    if (!feedme_is_logged_in()) return null;
    return feedme_get_user_by_id(feedme_current_user_id());
}

/* =========================================================
   ADDRESS HELPERS
========================================================= */
function feedme_normalize_address_input($data) {
    return [
        'building_number' => sanitize_text_field($data['building_number'] ?? ''),
        'street_name'     => sanitize_text_field($data['street_name'] ?? ''),
        'suburb_city'     => sanitize_text_field($data['suburb_city'] ?? ''),
        'postcode'        => intval($data['postcode'] ?? 0),
    ];
}

function feedme_find_address($address) {
    $db = feedme_db();
    $table = feedme_t(FEEDME_TABLE_ADDRESS);

    return $db->get_row(
        $db->prepare(
            "SELECT * FROM {$table}
             WHERE building_number = %s
               AND street_name = %s
               AND suburb_city = %s
               AND postcode = %d
             LIMIT 1",
            $address['building_number'],
            $address['street_name'],
            $address['suburb_city'],
            $address['postcode']
        )
    );
}

function feedme_insert_address($address) {
    $db = feedme_db();
    $table = str_replace('`', '', feedme_t(FEEDME_TABLE_ADDRESS));

    $db->insert(
        $table,
        [
            'building_number' => $address['building_number'],
            'street_name'     => $address['street_name'],
            'suburb_city'     => $address['suburb_city'],
            'postcode'        => $address['postcode'],
        ],
        ['%s', '%s', '%s', '%d']
    );

    return intval($db->insert_id);
}

function feedme_link_user_address($user_id, $address_id) {
    $db = feedme_db();
    $table = feedme_t(FEEDME_TABLE_USER_ADDRESS);

    $exists = $db->get_var(
        $db->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND address_id = %d",
            $user_id,
            $address_id
        )
    );

    if (!$exists) {
        $db->insert(
            str_replace('`', '', $table),
            [
                'user_id'    => $user_id,
                'address_id' => $address_id,
            ],
            ['%d', '%d']
        );
    }
}

function feedme_get_user_addresses($user_id) {
    $db = feedme_db();
    $ua = feedme_t(FEEDME_TABLE_USER_ADDRESS);
    $a  = feedme_t(FEEDME_TABLE_ADDRESS);

    return $db->get_results(
        $db->prepare(
            "SELECT a.*
             FROM {$ua} ua
             INNER JOIN {$a} a ON ua.address_id = a.address_id
             WHERE ua.user_id = %d
             ORDER BY a.address_id DESC",
            $user_id
        )
    );
}

function feedme_get_last_used_address($user_id) {
    $db = feedme_db();
    $orders = feedme_t(FEEDME_TABLE_ORDERS);
    $address = feedme_t(FEEDME_TABLE_ADDRESS);

    $row = $db->get_row(
        $db->prepare(
            "SELECT a.*
             FROM {$orders} o
             INNER JOIN {$address} a ON o.address_id = a.address_id
             WHERE o.user_id = %d
             ORDER BY o.order_id DESC
             LIMIT 1",
            $user_id
        )
    );

    if ($row) return $row;

    $addresses = feedme_get_user_addresses($user_id);
    return !empty($addresses) ? $addresses[0] : null;
}

function feedme_resolve_address_for_user($user_id, $address_input) {
    $address = feedme_normalize_address_input($address_input);

    if (
        $address['building_number'] === '' ||
        $address['street_name'] === '' ||
        $address['suburb_city'] === '' ||
        $address['postcode'] === 0
    ) {
        return 0;
    }

    $existing = feedme_find_address($address);

    if ($existing) {
        $address_id = intval($existing->address_id);
    } else {
        $address_id = feedme_insert_address($address);
    }

    if ($address_id > 0) {
        feedme_link_user_address($user_id, $address_id);
    }

    return $address_id;
}

/* =========================================================
   PROMO HELPERS
========================================================= */
function feedme_get_promo_by_code($code) {
    if (!$code) return null;

    $db = feedme_db();
    $promo = feedme_t(FEEDME_TABLE_PROMO_CODE);
    $today = date('Y-m-d');

    return $db->get_row(
        $db->prepare(
            "SELECT *
             FROM {$promo}
             WHERE code = %s
               AND (expiration_date IS NULL OR expiration_date >= %s)
             LIMIT 1",
            sanitize_text_field($code),
            $today
        )
    );
}

/* =========================================================
   RESTAURANT / MENU HELPERS
========================================================= */
function feedme_get_restaurant_by_id($restaurant_id) {
    $db = feedme_db();
    $restaurants = feedme_t(FEEDME_TABLE_RESTAURANTS);

    return $db->get_row(
        $db->prepare(
            "SELECT * FROM {$restaurants} WHERE restaurant_id = %d LIMIT 1",
            $restaurant_id
        )
    );
}

function feedme_get_restaurant_by_name($name) {
    $db = feedme_db();
    $restaurants = feedme_t(FEEDME_TABLE_RESTAURANTS);

    return $db->get_row(
        $db->prepare(
            "SELECT * FROM {$restaurants} WHERE name = %s LIMIT 1",
            $name
        )
    );
}

function feedme_get_menu_item_by_id($menu_id) {
    $db = feedme_db();
    $menu = feedme_t(FEEDME_TABLE_MENU);

    return $db->get_row(
        $db->prepare(
            "SELECT * FROM {$menu} WHERE menu_id = %d LIMIT 1",
            $menu_id
        )
    );
}

function feedme_get_order_items($order_id) {
    $db = feedme_db();
    $order_items = feedme_t(FEEDME_TABLE_ORDER_ITEMS);
    $menu = feedme_t(FEEDME_TABLE_MENU);

    return $db->get_results(
        $db->prepare(
            "SELECT oi.*, m.item_name
             FROM {$order_items} oi
             LEFT JOIN {$menu} m ON oi.menu_item_id = m.menu_id
             WHERE oi.order_id = %d",
            $order_id
        )
    );
}

/* =========================================================
   AUTH SHORTCODE
========================================================= */
function feedme_auth_shortcode() {
    $message = '';

    if (isset($_GET['feedme_logout']) && $_GET['feedme_logout'] === '1') {
        feedme_logout_user();
        $message = '<div class="feedme-message success">You have been logged out.</div>';
    }

    if (!empty($_POST['feedme_register_submit'])) {
        $db = feedme_db();
        $user_table = str_replace('`', '', feedme_t(FEEDME_TABLE_USER));

        $first_name   = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name    = sanitize_text_field($_POST['last_name'] ?? '');
        $email        = sanitize_email($_POST['email'] ?? '');
        $password     = $_POST['password'] ?? '';
        $confirm      = $_POST['confirm_password'] ?? '';
        $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');

        if ($first_name === '' || $last_name === '' || $email === '' || $password === '') {
            $message = '<div class="feedme-message error">Please complete all required fields.</div>';
        } elseif (!is_email($email)) {
            $message = '<div class="feedme-message error">Please enter a valid email address.</div>';
        } elseif ($password !== $confirm) {
            $message = '<div class="feedme-message error">Passwords do not match.</div>';
        } elseif (feedme_get_user_by_email($email)) {
            $message = '<div class="feedme-message error">An account with that email already exists.</div>';
        } else {
            $password_to_store = password_hash($password, PASSWORD_DEFAULT);

            $db->insert(
                $user_table,
                [
                    'first_name'   => $first_name,
                    'last_name'    => $last_name,
                    'email'        => $email,
                    'password'     => $password_to_store,
                    'phone_number' => $phone_number,
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );

            $new_user_id = intval($db->insert_id);
            if ($new_user_id > 0) {
                $new_user = feedme_get_user_by_id($new_user_id);
                if ($new_user) {
                    feedme_login_user($new_user);
                    wp_safe_redirect(site_url('/my-account/'));
                    exit;
                }
            }

            $message = '<div class="feedme-message error">Unable to create account.</div>';
        }
    }

    if (!empty($_POST['feedme_login_submit'])) {
        $email    = sanitize_email($_POST['login_email'] ?? '');
        $password = $_POST['login_password'] ?? '';
        $user = feedme_get_user_by_email($email);

        if (!$user) {
            $message = '<div class="feedme-message error">Invalid email or password.</div>';
        } else {
            $stored = (string) $user->password;
            $valid = false;

            if ($stored !== '') {
                if (password_verify($password, $stored)) {
                    $valid = true;
                } elseif ($password === $stored) {
                    $valid = true;
                }
            }

            if ($valid) {
                feedme_login_user($user);
                wp_safe_redirect(site_url('/my-account/'));
                exit;
            } else {
                $message = '<div class="feedme-message error">Invalid email or password.</div>';
            }
        }
    }

    if (feedme_is_logged_in()) {
        return '
        <section class="feedme-auth-page">
          <style>
            .feedme-auth-page{font-family:Arial,sans-serif;background:#fff;color:#012458;padding:60px 20px}
            .feedme-auth-wrap{max-width:900px;margin:0 auto}
            .feedme-box{background:#fff;border:1px solid #dfe7f5;border-radius:18px;padding:28px;box-shadow:0 8px 18px rgba(0,0,0,.06)}
            .feedme-box a{display:inline-block;background:#05f040;color:#012458;text-decoration:none;font-weight:bold;padding:10px 14px;border-radius:10px;margin-right:10px}
          </style>
          <div class="feedme-auth-wrap">
            <div class="feedme-box">
              <h2>You are already logged in.</h2>
              <p><a href="' . esc_url(site_url('/my-account/')) . '">Go to My Account</a><a href="' . esc_url(add_query_arg('feedme_logout', '1', site_url('/login/'))) . '">Logout</a></p>
            </div>
          </div>
        </section>';
    }

    ob_start();
    ?>
    <section class="feedme-auth-page">
      <style>
        .feedme-auth-page {
          font-family: Arial, sans-serif;
          background: #fff;
          color: #012458;
          padding: 60px 20px;
        }
        .feedme-auth-wrap {
          max-width: 1100px;
          margin: 0 auto;
        }
        .feedme-auth-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 24px;
        }
        .feedme-box {
          background: #fff;
          border: 1px solid #dfe7f5;
          border-radius: 18px;
          padding: 28px;
          box-shadow: 0 8px 18px rgba(0,0,0,0.06);
        }
        .feedme-box h2 {
          margin: 0 0 10px;
          color: #012458;
        }
        .feedme-box p {
          color: #555;
          line-height: 1.6;
          margin-bottom: 18px;
        }
        .feedme-box input {
          width: 100%;
          padding: 12px 14px;
          margin-bottom: 14px;
          border: 1px solid #ccd8ef;
          border-radius: 10px;
          box-sizing: border-box;
          font-size: 0.95rem;
        }
        .feedme-box button {
          background: #05f040;
          color: #012458;
          border: none;
          padding: 12px 16px;
          border-radius: 10px;
          font-weight: bold;
          cursor: pointer;
        }
        .feedme-message {
          max-width: 1100px;
          margin: 0 auto 20px;
          padding: 14px 16px;
          border-radius: 12px;
          font-weight: bold;
        }
        .feedme-message.error {
          background: #fff4e8;
          color: #9a5a00;
          border: 1px solid #f0cf9c;
        }
        .feedme-message.success {
          background: #e8fff0;
          color: #11612e;
          border: 1px solid #bdeccc;
        }
        @media (max-width: 800px) {
          .feedme-auth-grid {
            grid-template-columns: 1fr;
          }
        }
      </style>

      <?php echo $message; ?>

      <div class="feedme-auth-wrap">
        <div class="feedme-auth-grid">
          <div class="feedme-box">
            <h2>Login</h2>
            <p>Log in to access your account and orders.</p>
            <form method="post">
              <input type="email" name="login_email" placeholder="Email address" required>
              <input type="password" name="login_password" placeholder="Password" required>
              <button type="submit" name="feedme_login_submit">Login</button>
            </form>
          </div>

          <div class="feedme-box">
            <h2>Create Account</h2>
            <p>Create a new FeedMe account.</p>
            <form method="post">
              <input type="text" name="first_name" placeholder="First name" required>
              <input type="text" name="last_name" placeholder="Last name" required>
              <input type="email" name="email" placeholder="Email address" required>
              <input type="text" name="phone_number" placeholder="Phone number">
              <input type="password" name="password" placeholder="Password" required>
              <input type="password" name="confirm_password" placeholder="Confirm password" required>
              <button type="submit" name="feedme_register_submit">Create Account</button>
            </form>
          </div>
        </div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('feedme_auth', 'feedme_auth_shortcode');
add_shortcode('feedme_login', 'feedme_auth_shortcode');

/* =========================================================
   ACCOUNT PAGE
========================================================= */
function feedme_account_shortcode() {
    if (!feedme_is_logged_in()) {
        return '<section class="feedme-account-page"><div class="feedme-wrap"><div class="feedme-card"><p>Please <a href="' . esc_url(site_url('/login/')) . '">log in</a> to view your account.</p></div></div></section>';
    }

    $profile = feedme_get_current_user_profile();
    if (!$profile) {
        return '<section class="feedme-account-page"><div class="feedme-wrap"><div class="feedme-card"><p>No profile found for this account.</p></div></div></section>';
    }

    $addresses = feedme_get_user_addresses((int)$profile->user_id);
    $last_address = feedme_get_last_used_address((int)$profile->user_id);

    ob_start();
    ?>
    <section class="feedme-account-page">
      <style>
        .feedme-account-page {
          font-family: Arial, sans-serif;
          background: #fff;
          color: #012458;
          padding: 60px 20px;
        }
        .feedme-wrap {
          max-width: 1050px;
          margin: 0 auto;
        }
        .feedme-wrap h1 {
          margin: 0 0 20px;
          color: #012458;
        }
        .feedme-grid-2 {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 24px;
        }
        .feedme-card {
          background: #fff;
          border: 1px solid #dfe7f5;
          border-radius: 18px;
          padding: 24px;
          box-shadow: 0 8px 18px rgba(0,0,0,0.06);
        }
        .feedme-card h3 {
          margin-top: 0;
          color: #012458;
        }
        .feedme-card p {
          margin: 10px 0;
          color: #444;
        }
        .feedme-links {
          margin-top: 20px;
        }
        .feedme-links a {
          display: inline-block;
          background: #05f040;
          color: #012458;
          text-decoration: none;
          font-weight: bold;
          padding: 10px 14px;
          border-radius: 10px;
          margin-right: 10px;
          margin-bottom: 10px;
        }
        .feedme-address-box {
          border: 1px solid #e6edf8;
          border-radius: 12px;
          padding: 12px 14px;
          margin-bottom: 12px;
          background: #fbfdff;
        }
        .feedme-input {
          width: 100%;
          padding: 12px 14px;
          margin-bottom: 14px;
          border: 1px solid #ccd8ef;
          border-radius: 10px;
          box-sizing: border-box;
        }
        .feedme-btn {
          background: #05f040;
          color: #012458;
          border: none;
          padding: 12px 16px;
          border-radius: 10px;
          font-weight: bold;
          cursor: pointer;
        }
        @media (max-width: 800px) {
          .feedme-grid-2 {
            grid-template-columns: 1fr;
          }
        }
      </style>

      <div class="feedme-wrap">
        <h1>My Account</h1>

        <div class="feedme-grid-2">
          <div class="feedme-card">
            <h3>Profile Details</h3>
            <p><strong>First Name:</strong> <?php echo esc_html($profile->first_name); ?></p>
            <p><strong>Last Name:</strong> <?php echo esc_html($profile->last_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($profile->email); ?></p>
            <p><strong>Phone Number:</strong> <?php echo esc_html($profile->phone_number); ?></p>

            <div class="feedme-links">
              <a href="<?php echo esc_url(site_url('/my-orders/')); ?>">My Orders</a>
              <a href="<?php echo esc_url(add_query_arg('feedme_logout', '1', site_url('/login/'))); ?>">Logout</a>
            </div>
          </div>

          <div class="feedme-card">
            <h3>Saved Addresses</h3>

            <?php if ($last_address): ?>
              <div class="feedme-address-box">
                <p><strong>Last Used Address</strong></p>
                <p><?php echo esc_html($last_address->building_number . ' ' . $last_address->street_name); ?></p>
                <p><?php echo esc_html($last_address->suburb_city . ' ' . $last_address->postcode); ?></p>
              </div>
            <?php endif; ?>

            <?php if (!empty($addresses)): ?>
              <?php foreach ($addresses as $address): ?>
                <div class="feedme-address-box">
                  <p><?php echo esc_html($address->building_number . ' ' . $address->street_name); ?></p>
                  <p><?php echo esc_html($address->suburb_city . ' ' . $address->postcode); ?></p>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p>No saved addresses found.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="feedme-card" style="margin-top:24px;">
          <h3>Update Details</h3>
          <form method="post">
            <div class="feedme-grid-2">
              <div>
                <label>First Name</label>
                <input class="feedme-input" type="text" name="first_name" value="<?php echo esc_attr($profile->first_name ?? ''); ?>">
              </div>
              <div>
                <label>Last Name</label>
                <input class="feedme-input" type="text" name="last_name" value="<?php echo esc_attr($profile->last_name ?? ''); ?>">
              </div>
            </div>

            <label>Phone Number</label>
            <input class="feedme-input" type="text" name="phone_number" value="<?php echo esc_attr($profile->phone_number ?? ''); ?>">

            <div class="feedme-grid-2">
              <div>
                <label>Building Number</label>
                <input class="feedme-input" type="text" name="building_number" value="<?php echo esc_attr($last_address->building_number ?? ''); ?>">
              </div>
              <div>
                <label>Street Name</label>
                <input class="feedme-input" type="text" name="street_name" value="<?php echo esc_attr($last_address->street_name ?? ''); ?>">
              </div>
            </div>

            <div class="feedme-grid-2">
              <div>
                <label>Suburb / City</label>
                <input class="feedme-input" type="text" name="suburb_city" value="<?php echo esc_attr($last_address->suburb_city ?? ''); ?>">
              </div>
              <div>
                <label>Postcode</label>
                <input class="feedme-input" type="text" name="postcode" value="<?php echo esc_attr($last_address->postcode ?? ''); ?>">
              </div>
            </div>

            <div class="feedme-grid-2">
              <div>
                <label>New Password</label>
                <input class="feedme-input" type="password" name="new_password" placeholder="Leave blank to keep current password">
              </div>
              <div>
                <label>Confirm New Password</label>
                <input class="feedme-input" type="password" name="confirm_new_password" placeholder="Confirm new password">
              </div>
            </div>

            <button class="feedme-btn" type="submit" name="feedme_account_update_submit">Save Changes</button>
          </form>
        </div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('feedme_account', 'feedme_account_shortcode');

/* =========================================================
   ACCOUNT UPDATE HANDLER
========================================================= */
add_action('init', function () {
    if (!feedme_is_logged_in()) return;
    if (empty($_POST['feedme_account_update_submit'])) return;

    $db = feedme_db();
    $user_table = str_replace('`', '', feedme_t(FEEDME_TABLE_USER));
    $user_id = feedme_current_user_id();

    $first_name   = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name    = sanitize_text_field($_POST['last_name'] ?? '');
    $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');

    $db->update(
        $user_table,
        [
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'phone_number' => $phone_number
        ],
        ['user_id' => $user_id],
        ['%s', '%s', '%s'],
        ['%d']
    );

    $address_input = [
        'building_number' => sanitize_text_field($_POST['building_number'] ?? ''),
        'street_name'     => sanitize_text_field($_POST['street_name'] ?? ''),
        'suburb_city'     => sanitize_text_field($_POST['suburb_city'] ?? ''),
        'postcode'        => intval($_POST['postcode'] ?? 0),
    ];

    feedme_resolve_address_for_user($user_id, $address_input);

    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';

    if ($new_password !== '' && $new_password === $confirm_password) {
        $db->update(
            $user_table,
            ['password' => password_hash($new_password, PASSWORD_DEFAULT)],
            ['user_id' => $user_id],
            ['%s'],
            ['%d']
        );
    }

    wp_safe_redirect(site_url('/my-account/'));
    exit;
});

/* =========================================================
   REORDER HELPERS
========================================================= */
function feedme_reorder_order($old_order_id, $user_id) {
    $db = feedme_db();
    $orders = feedme_t(FEEDME_TABLE_ORDERS);
    $order_items = feedme_t(FEEDME_TABLE_ORDER_ITEMS);

    $old_order = $db->get_row(
        $db->prepare(
            "SELECT * FROM {$orders} WHERE order_id = %d AND user_id = %d LIMIT 1",
            $old_order_id,
            $user_id
        )
    );

    if (!$old_order) {
        return false;
    }

    $db->insert(
        str_replace('`', '', $orders),
        [
            'restaurant_id'  => intval($old_order->restaurant_id),
            'user_id'        => intval($user_id),
            'address_id'     => intval($old_order->address_id),
            'promo_id'       => $old_order->promo_id !== null ? intval($old_order->promo_id) : null,
            'order_type'     => sanitize_text_field($old_order->order_type),
            'total_price'    => floatval($old_order->total_price),
            'order_status'   => 'Pending',
            'payment_method' => sanitize_text_field($old_order->payment_method),
        ],
        ['%d', '%d', '%d', '%d', '%s', '%f', '%s', '%s']
    );

    $new_order_id = intval($db->insert_id);

    if ($new_order_id <= 0) {
        return false;
    }

    $items = $db->get_results(
        $db->prepare(
            "SELECT * FROM {$order_items} WHERE order_id = %d",
            $old_order_id
        )
    );

    if ($items) {
        foreach ($items as $item) {
            $db->insert(
                str_replace('`', '', $order_items),
                [
                    'order_id'     => $new_order_id,
                    'menu_item_id' => intval($item->menu_item_id),
                    'quantity'     => intval($item->quantity),
                    'price'        => floatval($item->price),
                ],
                ['%d', '%d', '%d', '%f']
            );
        }
    }

    return $new_order_id;
}

/* =========================================================
   ORDERS PAGE
========================================================= */
function feedme_orders_shortcode() {
    if (!feedme_is_logged_in()) {
        return '<section class="feedme-orders-page"><div class="feedme-wrap"><div class="feedme-card"><p>Please <a href="' . esc_url(site_url('/login/')) . '">log in</a> to view your orders.</p></div></div></section>';
    }

    $profile = feedme_get_current_user_profile();
    if (!$profile) {
        return '<section class="feedme-orders-page"><div class="feedme-wrap"><div class="feedme-card"><p>No profile found for this account.</p></div></div></section>';
    }

    $message = '';

    if (!empty($_POST['feedme_reorder_submit'])) {
        $old_order_id = intval($_POST['reorder_order_id'] ?? 0);
        $new_order_id = feedme_reorder_order($old_order_id, intval($profile->user_id));

        if ($new_order_id) {
            $message = '<div class="feedme-message success">The previous order has been added again as a new order. New Order ID: ' . esc_html($new_order_id) . '</div>';
        } else {
            $message = '<div class="feedme-message error">Unable to reorder that order.</div>';
        }
    }

    $db = feedme_db();
    $orders_table = feedme_t(FEEDME_TABLE_ORDERS);
    $restaurants_table = feedme_t(FEEDME_TABLE_RESTAURANTS);

    $orders = $db->get_results(
        $db->prepare(
            "SELECT o.*, r.name AS restaurant_name
             FROM {$orders_table} o
             LEFT JOIN {$restaurants_table} r ON o.restaurant_id = r.restaurant_id
             WHERE o.user_id = %d
             ORDER BY o.order_id DESC",
            $profile->user_id
        )
    );

    $current_orders = [];
    $previous_orders = [];

    if ($orders) {
        $first = true;
        foreach ($orders as $order) {
            if ($first) {
                $current_orders[] = $order;
                $first = false;
            } else {
                $previous_orders[] = $order;
            }
        }
    }

    ob_start();
    ?>
    <section class="feedme-orders-page">
      <style>
        .feedme-orders-page {
          font-family: Arial, sans-serif;
          background: #fff;
          color: #012458;
          padding: 60px 20px;
        }
        .feedme-wrap {
          max-width: 1100px;
          margin: 0 auto;
        }
        .feedme-wrap h1 {
          margin: 0 0 20px;
        }
        .feedme-wrap h2 {
          margin: 30px 0 15px;
          color: #012458;
        }
        .feedme-order-card {
          background: #fff;
          border: 1px solid #dfe7f5;
          border-radius: 18px;
          padding: 22px;
          box-shadow: 0 8px 18px rgba(0,0,0,0.06);
          margin-bottom: 18px;
        }
        .feedme-order-card h3 {
          margin-top: 0;
        }
        .feedme-order-card p {
          margin: 8px 0;
          color: #444;
        }
        .feedme-item-list {
          margin-top: 14px;
          border-top: 1px solid #e6edf8;
          padding-top: 12px;
        }
        .feedme-item-row {
          padding: 8px 0;
          border-bottom: 1px solid #f1f5fb;
        }
        .feedme-item-row:last-child {
          border-bottom: none;
        }
        .feedme-order-card button {
          background: #05f040;
          color: #012458;
          border: none;
          padding: 10px 14px;
          border-radius: 10px;
          font-weight: bold;
          cursor: pointer;
        }
        .feedme-message {
          max-width: 1100px;
          margin: 0 auto 20px;
          padding: 14px 16px;
          border-radius: 12px;
          font-weight: bold;
        }
        .feedme-message.success {
          background: #e8fff0;
          color: #11612e;
          border: 1px solid #bdeccc;
        }
        .feedme-message.error {
          background: #fff4e8;
          color: #9a5a00;
          border: 1px solid #f0cf9c;
        }
      </style>

      <?php echo $message; ?>

      <div class="feedme-wrap">
        <h1>My Orders</h1>

        <h2>Current Order</h2>
        <?php if (!$current_orders): ?>
          <div class="feedme-order-card">
            <p>No current order found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($current_orders as $order): ?>
            <?php $items = feedme_get_order_items((int)$order->order_id); ?>
            <div class="feedme-order-card">
              <h3>Order #<?php echo esc_html($order->order_id); ?></h3>
              <p><strong>Restaurant:</strong> <?php echo esc_html($order->restaurant_name ?: 'Unknown Restaurant'); ?></p>
              <p><strong>Order Type:</strong> <?php echo esc_html($order->order_type); ?></p>
              <p><strong>Payment Method:</strong> <?php echo esc_html($order->payment_method); ?></p>
              <p><strong>Total Price:</strong> $<?php echo esc_html(number_format((float)$order->total_price, 2)); ?></p>
              <p><strong>Status:</strong> <?php echo esc_html($order->order_status); ?></p>

              <?php if ($items): ?>
                <div class="feedme-item-list">
                  <p><strong>Items:</strong></p>
                  <?php foreach ($items as $item): ?>
                    <div class="feedme-item-row">
                      <?php echo esc_html($item->item_name ?: ('Menu Item #' . $item->menu_item_id)); ?>
                      — Qty: <?php echo esc_html($item->quantity); ?>
                      — $<?php echo esc_html(number_format((float)$item->price, 2)); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <h2>Previous Orders</h2>
        <?php if (!$previous_orders): ?>
          <div class="feedme-order-card">
            <p>No previous orders found.</p>
          </div>
        <?php else: ?>
          <?php foreach ($previous_orders as $order): ?>
            <?php $items = feedme_get_order_items((int)$order->order_id); ?>
            <div class="feedme-order-card">
              <h3>Order #<?php echo esc_html($order->order_id); ?></h3>
              <p><strong>Restaurant:</strong> <?php echo esc_html($order->restaurant_name ?: 'Unknown Restaurant'); ?></p>
              <p><strong>Order Type:</strong> <?php echo esc_html($order->order_type); ?></p>
              <p><strong>Payment Method:</strong> <?php echo esc_html($order->payment_method); ?></p>
              <p><strong>Total Price:</strong> $<?php echo esc_html(number_format((float)$order->total_price, 2)); ?></p>
              <p><strong>Status:</strong> <?php echo esc_html($order->order_status); ?></p>

              <?php if ($items): ?>
                <div class="feedme-item-list">
                  <p><strong>Items:</strong></p>
                  <?php foreach ($items as $item): ?>
                    <div class="feedme-item-row">
                      <?php echo esc_html($item->item_name ?: ('Menu Item #' . $item->menu_item_id)); ?>
                      — Qty: <?php echo esc_html($item->quantity); ?>
                      — $<?php echo esc_html(number_format((float)$item->price, 2)); ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <form method="post">
                <input type="hidden" name="reorder_order_id" value="<?php echo esc_attr($order->order_id); ?>">
                <button type="submit" name="feedme_reorder_submit">Reorder Previous Order</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('feedme_orders', 'feedme_orders_shortcode');

/* =========================================================
   AJAX: LAST USED ADDRESS
========================================================= */
add_action('wp_ajax_feedme_get_last_address', 'feedme_get_last_address_ajax');
add_action('wp_ajax_nopriv_feedme_get_last_address', 'feedme_get_last_address_ajax');

function feedme_get_last_address_ajax() {
    if (!feedme_is_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }

    $user_id = feedme_current_user_id();
    $address = feedme_get_last_used_address($user_id);

    if (!$address) {
        wp_send_json_error(['message' => 'No saved address found']);
    }

    wp_send_json_success([
        'building_number' => $address->building_number,
        'street_name'     => $address->street_name,
        'suburb_city'     => $address->suburb_city,
        'postcode'        => $address->postcode,
    ]);
}

/* =========================================================
   AJAX: SUBMIT ORDER
========================================================= */
add_action('wp_ajax_feedme_submit_order', 'feedme_submit_order_ajax');
add_action('wp_ajax_nopriv_feedme_submit_order', 'feedme_submit_order_ajax');

function feedme_submit_order_ajax() {
    if (!feedme_is_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to place an order.']);
    }

    $db = feedme_db();
    $orders_table = str_replace('`', '', feedme_t(FEEDME_TABLE_ORDERS));
    $order_items_table = str_replace('`', '', feedme_t(FEEDME_TABLE_ORDER_ITEMS));

    $user_id = feedme_current_user_id();
    $payload = json_decode(file_get_contents('php://input'), true);

    if (!$payload || empty($payload['restaurant_id']) || empty($payload['items']) || empty($payload['payment_method'])) {
        wp_send_json_error(['message' => 'Invalid order data.']);
    }

    $restaurant_id  = intval($payload['restaurant_id']);
    $order_type     = sanitize_text_field($payload['order_type'] ?? 'Delivery');
    $payment_method = sanitize_text_field($payload['payment_method'] ?? '');
    $promo_code     = sanitize_text_field($payload['promo_code'] ?? '');
    $items          = is_array($payload['items']) ? $payload['items'] : [];
    $address_input  = is_array($payload['address']) ? $payload['address'] : [];
    $client_total   = floatval($payload['total_price'] ?? 0);

    $address_id = feedme_resolve_address_for_user($user_id, $address_input);
    if ($address_id <= 0) {
        wp_send_json_error(['message' => 'Please enter a valid address.']);
    }

    $promo = $promo_code ? feedme_get_promo_by_code($promo_code) : null;
    $promo_id = $promo ? intval($promo->promo_id) : null;
    $discount_percentage = $promo ? intval($promo->discount_percentage) : 0;

    $calculated_subtotal = 0;
    foreach ($items as $item) {
        $menu_item_id = intval($item['menu_item_id'] ?? 0);
        $quantity     = intval($item['quantity'] ?? 0);
        $price        = floatval($item['price'] ?? 0);

        if ($menu_item_id > 0 && $quantity > 0) {
            $calculated_subtotal += ($price * $quantity);
        }
    }

    $discount_amount = 0;
    if ($discount_percentage > 0) {
        $discount_amount = ($calculated_subtotal * $discount_percentage) / 100;
    }

    $final_total = $client_total > 0 ? $client_total : ($calculated_subtotal - $discount_amount);

    $db->insert(
        $orders_table,
        [
            'restaurant_id'  => $restaurant_id,
            'user_id'        => $user_id,
            'address_id'     => $address_id,
            'promo_id'       => $promo_id,
            'order_type'     => $order_type,
            'total_price'    => $final_total,
            'order_status'   => 'Pending',
            'payment_method' => $payment_method,
        ],
        ['%d', '%d', '%d', '%d', '%s', '%f', '%s', '%s']
    );

    $order_id = intval($db->insert_id);
    if ($order_id <= 0) {
        wp_send_json_error(['message' => 'Unable to save order.']);
    }

    foreach ($items as $item) {
        $menu_item_id = intval($item['menu_item_id'] ?? 0);
        $quantity     = intval($item['quantity'] ?? 0);
        $price        = floatval($item['price'] ?? 0);

        if ($menu_item_id <= 0 || $quantity <= 0) continue;

        $db->insert(
            $order_items_table,
            [
                'order_id'     => $order_id,
                'menu_item_id' => $menu_item_id,
                'quantity'     => $quantity,
                'price'        => $price,
            ],
            ['%d', '%d', '%d', '%f']
        );
    }

    wp_send_json_success([
        'message'     => 'Order placed successfully.',
        'order_id'    => $order_id,
        'address_id'  => $address_id,
        'total_price' => $final_total
    ]);
}

/* =========================================================
   HEADER CART SHORTCODE
========================================================= */
function feedme_header_cart_shortcode() {
    ob_start();
    ?>
    <div class="feedme-header-cart" style="display:flex;align-items:center;gap:8px;">
      <span style="font-size:1.2rem;">🛒</span>
      <span id="feedme-cart-count">0</span>
    </div>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        try {
          const savedCart = JSON.parse(localStorage.getItem("feedme_cart") || "[]");
          let count = 0;
          savedCart.forEach(item => {
            count += Number(item.quantity || 0);
          });
          const cartCount = document.getElementById("feedme-cart-count");
          if (cartCount) cartCount.textContent = count;
        } catch (e) {}
      });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('feedme_header_cart', 'feedme_header_cart_shortcode');