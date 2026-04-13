<?php
/*
Plugin Name: FeedMe Portal
Description: FeedMe login, account, orders, address handling, and restaurant order integration.
Version: 3.0
*/

add_action('init', function () {
    if (!session_id()) {
        session_start();
    }
}, 1);

/* =========================
   URL HELPERS
========================= */
function feedme_login_url() {
    return site_url('/?page_id=483');
}

function feedme_account_url() {
    return site_url('/?page_id=344');
}

function feedme_orders_url() {
    return site_url('/?page_id=47');
}

/* =========================
   LOGOUT HANDLER
========================= */
add_action('init', function () {
    if (isset($_GET['feedme_logout']) && $_GET['feedme_logout'] === '1') {
        if (!session_id()) {
            session_start();
        }

        unset($_SESSION['user_id'], $_SESSION['name']);
        wp_safe_redirect(feedme_login_url());
        exit;
    }
}, 5);

/* =========================
   DATABASE CONNECTION
========================= */
function feedme_db() {
    $host = '########################';
    $user = '########################';
    $pass = '########################';
    $name = '########################';
    $port = 3306;

    $mysqli = new mysqli($host, $user, $pass, '', $port);

    if ($mysqli->connect_error) {
        return null;
    }

    $mysqli->set_charset('utf8mb4');

    if (!$mysqli->select_db($name)) {
        return null;
    }

    return $mysqli;
}

/* =========================
   AUTH SHORTCODE
========================= */
function feedme_auth_shortcode() {
    $db = feedme_db();

    $db_connected = false;
    $db_message = 'Database Status = ✖ Not Connected';
    $form_message = '';

    if ($db) {
        $db_name_result = $db->query("SELECT DATABASE() AS db_name");
        $db_name_row = $db_name_result ? $db_name_result->fetch_assoc() : null;
        $current_db = $db_name_row['db_name'] ?? '';

        if (!empty($current_db)) {
            $db_connected = true;
            $db_message = 'Database Status = ✔ Connected to ' . $current_db;
        }
    }

    if ($db && isset($_POST['register'])) {
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');

        if ($first === '' || $last === '' || $email === '' || $pass === '') {
            $form_message = '✖ Please complete all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_message = '✖ Please enter a valid email address.';
        } elseif ($pass !== $confirm) {
            $form_message = '✖ Passwords do not match.';
        } else {
            $check_stmt = $db->prepare("SELECT user_id FROM `User` WHERE email = ? LIMIT 1");

            if (!$check_stmt) {
                $form_message = 'Create Account Failed: ' . $db->error;
            } else {
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $existing_user = $check_result ? $check_result->fetch_assoc() : null;
                $check_stmt->close();

                if ($existing_user) {
                    $form_message = '✖ An account with that email already exists.';
                } else {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);

                    $stmt = $db->prepare("
                        INSERT INTO `User` (`first_name`, `last_name`, `email`, `password`, `phone_number`)
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    if (!$stmt) {
                        $form_message = 'Create Account Failed: ' . $db->error;
                    } else {
                        $stmt->bind_param("sssss", $first, $last, $email, $hash, $phone);

                        if ($stmt->execute()) {
                            $_SESSION['user_id'] = (int) $stmt->insert_id;
                            $_SESSION['name'] = $first;
                            $stmt->close();
                            wp_safe_redirect(feedme_account_url());
                            exit;
                        } else {
                            $form_message = 'Create Account Failed: ' . $stmt->error;
                            $stmt->close();
                        }
                    }
                }
            }
        }
    }

    if ($db && isset($_POST['login'])) {
        $email = trim($_POST['login_email'] ?? '');
        $pass  = $_POST['login_password'] ?? '';

        $stmt = $db->prepare("SELECT * FROM `User` WHERE `email` = ?");
        if (!$stmt) {
            $form_message = 'Login Failed: ' . $db->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;

            if ($user) {
                if (password_verify($pass, $user['password']) || $pass === $user['password']) {
                    $_SESSION['user_id'] = (int) $user['user_id'];
                    $_SESSION['name'] = $user['first_name'];
                    $stmt->close();
                    wp_safe_redirect(feedme_account_url());
                    exit;
                } else {
                    $form_message = '✖ Wrong password.';
                }
            } else {
                $form_message = '✖ User not found.';
            }

            $stmt->close();
        }
    }

    if (!empty($_SESSION['user_id'])) {
        return '
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
            .feedme-box {
              background: #fff;
              border: 1px solid #dfe7f5;
              border-radius: 18px;
              padding: 28px;
              box-shadow: 0 8px 18px rgba(0,0,0,0.06);
            }
            .feedme-box a {
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
            .feedme-db-status {
              display: inline-block;
              padding: 10px 14px;
              border-radius: 12px;
              font-weight: bold;
              margin-bottom: 18px;
              background: ' . ($db_connected ? '#e8fff0' : '#fff4e8') . ';
              color: ' . ($db_connected ? '#11612e' : '#9a5a00') . ';
              border: 1px solid ' . ($db_connected ? '#bdeccc' : '#f0cf9c') . ';
            }
          </style>
          <div class="feedme-auth-wrap">
            <div class="feedme-db-status">' . esc_html($db_message) . '</div>
            <div class="feedme-box">
              <h2>You are already logged in.</h2>
              <p><a href="' . esc_url(feedme_account_url()) . '">Go to My Account</a><a href="' . esc_url(add_query_arg('feedme_logout', '1', feedme_login_url())) . '">Logout</a></p>
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
        .feedme-auth-hero {
          background: linear-gradient(135deg, #012458 0%, #023a7a 100%);
          border-radius: 20px;
          padding: 45px 35px;
          color: #fff;
          box-shadow: 0 12px 28px rgba(0,0,0,0.12);
          margin-bottom: 24px;
        }
        .feedme-auth-hero h1 {
          margin: 0 0 12px;
          font-size: 2.5rem;
          color: #fff;
        }
        .feedme-auth-hero p {
          margin: 0;
          font-size: 1.05rem;
          line-height: 1.7;
          max-width: 760px;
          color: #e9f1ff;
        }
        .feedme-db-status {
          display: inline-block;
          padding: 10px 14px;
          border-radius: 12px;
          font-weight: bold;
          margin-bottom: 20px;
          background: <?php echo $db_connected ? '#e8fff0' : '#fff4e8'; ?>;
          color: <?php echo $db_connected ? '#11612e' : '#9a5a00'; ?>;
          border: 1px solid <?php echo $db_connected ? '#bdeccc' : '#f0cf9c'; ?>;
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
        .feedme-form-message {
          max-width: 1100px;
          margin: 0 auto 20px;
          padding: 14px 16px;
          border-radius: 12px;
          font-weight: bold;
          background: #fff4e8;
          color: #9a5a00;
          border: 1px solid #f0cf9c;
        }
        .feedme-form-message.success {
          background: #e8fff0;
          color: #11612e;
          border: 1px solid #bdeccc;
        }
        @media (max-width: 800px) {
          .feedme-auth-grid {
            grid-template-columns: 1fr;
          }
          .feedme-auth-hero h1 {
            font-size: 2rem;
          }
        }
      </style>

      <div class="feedme-auth-wrap">
        <div class="feedme-auth-hero">
          <h1>Welcome to FeedMe</h1>
          <p>Log in to manage your account, save your details, place orders faster, and view your current and previous orders all in one place.</p>
        </div>

        <div class="feedme-db-status">
          <?php echo esc_html($db_message); ?>
        </div>

        <?php if ($form_message): ?>
          <div class="feedme-form-message <?php echo (strpos($form_message, '✔') !== false) ? 'success' : ''; ?>">
            <?php echo esc_html($form_message); ?>
          </div>
        <?php endif; ?>

        <div class="feedme-auth-grid">
          <div class="feedme-box">
            <h2>Login</h2>
            <p>Log in to access your account details and orders.</p>
            <form method="post">
              <input type="email" name="login_email" placeholder="Email address" required>
              <input type="password" name="login_password" placeholder="Password" required>
              <button type="submit" name="login">Login</button>
            </form>
          </div>

          <div class="feedme-box">
            <h2>Create Account</h2>
            <p>Create a new FeedMe account.</p>
            <form method="post">
              <input type="text" name="first_name" placeholder="First name" required>
              <input type="text" name="last_name" placeholder="Last name" required>
              <input type="email" name="email" placeholder="Email address" required>
              <input type="text" name="phone" placeholder="Phone number">
              <input type="password" name="password" placeholder="Password" required>
              <input type="password" name="confirm_password" placeholder="Confirm password" required>
              <button type="submit" name="register">Create Account</button>
            </form>
          </div>
        </div>
      </div>
    </section>
    <?php

    return ob_get_clean();
}
add_shortcode('feedme_auth', 'feedme_auth_shortcode');

/* =========================
   ACCOUNT HELPERS
========================= */
function feedme_get_current_user_profile() {
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $db = feedme_db();
    if (!$db) {
        return null;
    }

    $user_id = (int) $_SESSION['user_id'];

    $stmt = $db->prepare("SELECT * FROM `User` WHERE `user_id` = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user;
}

function feedme_get_user_addresses($user_id) {
    $db = feedme_db();
    if (!$db) {
        return [];
    }

    $sql = "
        SELECT a.*
        FROM `User Address` ua
        INNER JOIN `Address` a ON ua.address_id = a.address_id
        WHERE ua.user_id = ?
        ORDER BY a.address_id DESC
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $addresses = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $addresses[] = $row;
        }
    }

    $stmt->close();
    return $addresses;
}

function feedme_get_last_used_address($user_id) {
    $db = feedme_db();
    if (!$db) {
        return null;
    }

    $sql = "
        SELECT a.*
        FROM `Orders` o
        INNER JOIN `Address` a ON o.address_id = a.address_id
        WHERE o.user_id = ?
        ORDER BY o.order_id DESC
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($address) {
        return $address;
    }

    $addresses = feedme_get_user_addresses($user_id);
    return !empty($addresses) ? $addresses[0] : null;
}

function feedme_find_existing_address($building_number, $street_name, $suburb_city, $postcode) {
    $db = feedme_db();
    if (!$db) {
        return null;
    }

    $sql = "
        SELECT *
        FROM `Address`
        WHERE building_number = ?
          AND street_name = ?
          AND suburb_city = ?
          AND postcode = ?
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("sssi", $building_number, $street_name, $suburb_city, $postcode);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $address;
}

function feedme_insert_address($building_number, $street_name, $suburb_city, $postcode) {
    $db = feedme_db();
    if (!$db) {
        return 0;
    }

    $stmt = $db->prepare("
        INSERT INTO `Address` (`building_number`, `street_name`, `suburb_city`, `postcode`)
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("sssi", $building_number, $street_name, $suburb_city, $postcode);

    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }

    $new_id = (int) $stmt->insert_id;
    $stmt->close();

    return $new_id;
}

function feedme_link_user_address($user_id, $address_id) {
    $db = feedme_db();
    if (!$db) {
        return;
    }

    $check = $db->prepare("
        SELECT *
        FROM `User Address`
        WHERE user_id = ? AND address_id = ?
        LIMIT 1
    ");

    if (!$check) {
        return;
    }

    $check->bind_param("ii", $user_id, $address_id);
    $check->execute();
    $result = $check->get_result();
    $exists = $result ? $result->fetch_assoc() : null;
    $check->close();

    if ($exists) {
        return;
    }

    $insert = $db->prepare("
        INSERT INTO `User Address` (`user_id`, `address_id`)
        VALUES (?, ?)
    ");

    if (!$insert) {
        return;
    }

    $insert->bind_param("ii", $user_id, $address_id);
    $insert->execute();
    $insert->close();
}

/* =========================
   ACCOUNT UPDATE
========================= */
add_action('init', function () {
    if (empty($_SESSION['user_id'])) {
        return;
    }

    if (!isset($_POST['feedme_account_update'])) {
        return;
    }

    $db = feedme_db();
    if (!$db) {
        return;
    }

    $user_id = (int) $_SESSION['user_id'];

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    $building_number = trim($_POST['building_number'] ?? '');
    $street_name = trim($_POST['street_name'] ?? '');
    $suburb_city = trim($_POST['suburb_city'] ?? '');
    $postcode = (int) ($_POST['postcode'] ?? 0);

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';

    $stmt = $db->prepare("
        UPDATE `User`
        SET first_name = ?, last_name = ?, phone_number = ?
        WHERE user_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("sssi", $first_name, $last_name, $phone_number, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    if (
        $building_number !== '' &&
        $street_name !== '' &&
        $suburb_city !== '' &&
        $postcode > 0
    ) {
        $existing_address = feedme_find_existing_address($building_number, $street_name, $suburb_city, $postcode);

        if ($existing_address) {
            $address_id = (int) $existing_address['address_id'];
        } else {
            $address_id = feedme_insert_address($building_number, $street_name, $suburb_city, $postcode);
        }

        if ($address_id > 0) {
            feedme_link_user_address($user_id, $address_id);
        }
    }

    if ($new_password !== '' && $new_password === $confirm_password) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        $pw_stmt = $db->prepare("
            UPDATE `User`
            SET `password` = ?
            WHERE user_id = ?
        ");

        if ($pw_stmt) {
            $pw_stmt->bind_param("si", $hash, $user_id);
            $pw_stmt->execute();
            $pw_stmt->close();
        }
    }

    wp_safe_redirect(feedme_account_url());
    exit;
});

/* =========================
   ACCOUNT SHORTCODE
========================= */
function feedme_account_shortcode() {
    if (empty($_SESSION['user_id'])) {
        return '
        <section class="feedme-account-page">
          <style>
            .feedme-account-page {
              font-family: Arial, sans-serif;
              background: #fff;
              color: #012458;
              padding: 60px 20px;
            }
            .feedme-wrap {
              max-width: 1000px;
              margin: 0 auto;
            }
            .feedme-card {
              background: #fff;
              border: 1px solid #dfe7f5;
              border-radius: 18px;
              padding: 24px;
              box-shadow: 0 8px 18px rgba(0,0,0,0.06);
            }
            .feedme-card a {
              color: #012458;
              font-weight: bold;
            }
          </style>
          <div class="feedme-wrap">
            <div class="feedme-card">
              <p>Please <a href="' . esc_url(feedme_login_url()) . '">log in</a> to view your account.</p>
            </div>
          </div>
        </section>';
    }

    $user_id = (int) $_SESSION['user_id'];
    $profile = feedme_get_current_user_profile();
    $addresses = feedme_get_user_addresses($user_id);
    $last_address = feedme_get_last_used_address($user_id);

    if (!$profile) {
        return '<p>Unable to load account details.</p>';
    }

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

        .feedme-account-hero {
          background: linear-gradient(135deg, #012458 0%, #023a7a 100%);
          border-radius: 20px;
          padding: 45px 35px;
          color: #fff;
          box-shadow: 0 12px 28px rgba(0,0,0,0.12);
          margin-bottom: 24px;
        }

        .feedme-account-hero h1 {
          margin: 0 0 12px;
          font-size: 2.5rem;
          color: #fff;
        }

        .feedme-account-hero p {
          margin: 0;
          font-size: 1.05rem;
          line-height: 1.7;
          max-width: 760px;
          color: #e9f1ff;
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

        .feedme-card h2,
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
          font-size: 0.95rem;
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

          .feedme-account-hero h1 {
            font-size: 2rem;
          }
        }
      </style>

      <div class="feedme-wrap">
        <div class="feedme-account-hero">
          <h1>My Account</h1>
          <p>View your personal details, saved addresses, and update your information. You can also jump straight to your orders page.</p>
        </div>

        <div class="feedme-grid-2">
          <div class="feedme-card">
            <h2>Profile Details</h2>
            <p><strong>First Name:</strong> <?php echo esc_html($profile['first_name']); ?></p>
            <p><strong>Last Name:</strong> <?php echo esc_html($profile['last_name']); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($profile['email']); ?></p>
            <p><strong>Phone Number:</strong> <?php echo esc_html($profile['phone_number']); ?></p>

            <div class="feedme-links">
              <a href="<?php echo esc_url(feedme_orders_url()); ?>">My Orders</a>
              <a href="<?php echo esc_url(add_query_arg('feedme_logout', '1', feedme_login_url())); ?>">Logout</a>
            </div>
          </div>

          <div class="feedme-card">
            <h2>Saved Addresses</h2>

            <?php if ($last_address): ?>
              <div class="feedme-address-box">
                <p><strong>Last Used Address</strong></p>
                <p><?php echo esc_html($last_address['building_number'] . ' ' . $last_address['street_name']); ?></p>
                <p><?php echo esc_html($last_address['suburb_city'] . ' ' . $last_address['postcode']); ?></p>
              </div>
            <?php endif; ?>

            <?php if (!empty($addresses)): ?>
              <?php foreach ($addresses as $address): ?>
                <div class="feedme-address-box">
                  <p><?php echo esc_html($address['building_number'] . ' ' . $address['street_name']); ?></p>
                  <p><?php echo esc_html($address['suburb_city'] . ' ' . $address['postcode']); ?></p>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p>No saved addresses found.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="feedme-card" style="margin-top:24px;">
          <h2>Update Details</h2>
          <form method="post">
            <div class="feedme-grid-2">
              <div>
                <label>First Name</label>
                <input class="feedme-input" type="text" name="first_name" value="<?php echo esc_attr($profile['first_name']); ?>">
              </div>
              <div>
                <label>Last Name</label>
                <input class="feedme-input" type="text" name="last_name" value="<?php echo esc_attr($profile['last_name']); ?>">
              </div>
            </div>

            <label>Phone Number</label>
            <input class="feedme-input" type="text" name="phone_number" value="<?php echo esc_attr($profile['phone_number']); ?>">

            <div class="feedme-grid-2">
              <div>
                <label>Building Number</label>
                <input class="feedme-input" type="text" name="building_number" value="<?php echo esc_attr($last_address['building_number'] ?? ''); ?>">
              </div>
              <div>
                <label>Street Name</label>
                <input class="feedme-input" type="text" name="street_name" value="<?php echo esc_attr($last_address['street_name'] ?? ''); ?>">
              </div>
            </div>

            <div class="feedme-grid-2">
              <div>
                <label>Suburb / City</label>
                <input class="feedme-input" type="text" name="suburb_city" value="<?php echo esc_attr($last_address['suburb_city'] ?? ''); ?>">
              </div>
              <div>
                <label>Postcode</label>
                <input class="feedme-input" type="text" name="postcode" value="<?php echo esc_attr($last_address['postcode'] ?? ''); ?>">
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

            <button class="feedme-btn" type="submit" name="feedme_account_update">Save Changes</button>
          </form>
        </div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('feedme_account', 'feedme_account_shortcode');

/* =========================
   ORDERS HELPERS
========================= */
function feedme_get_order_items($order_id) {
    $db = feedme_db();
    if (!$db) {
        return [];
    }

    $sql = "
        SELECT oi.order_id, oi.menu_item_id, oi.quantity, oi.price, m.item_name
        FROM `Order Items` oi
        LEFT JOIN `Menu` m ON oi.menu_item_id = m.menu_id
        WHERE oi.order_id = ?
        ORDER BY oi.menu_item_id ASC
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }

    $stmt->close();
    return $items;
}

function feedme_reorder_order($old_order_id, $user_id) {
    $db = feedme_db();
    if (!$db) {
        return false;
    }

    $order_stmt = $db->prepare("
        SELECT *
        FROM `Orders`
        WHERE order_id = ? AND user_id = ?
        LIMIT 1
    ");

    if (!$order_stmt) {
        return false;
    }

    $order_stmt->bind_param("ii", $old_order_id, $user_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $old_order = $order_result ? $order_result->fetch_assoc() : null;
    $order_stmt->close();

    if (!$old_order) {
        return false;
    }

    $restaurant_id  = (int) $old_order['restaurant_id'];
    $address_id     = (int) $old_order['address_id'];
    $order_type     = $old_order['order_type'];
    $total_price    = (float) $old_order['total_price'];
    $order_status   = 'Pending';
    $payment_method = $old_order['payment_method'];

    $promo_id = null;
    if (array_key_exists('promo_id', $old_order) && $old_order['promo_id'] !== null && $old_order['promo_id'] !== '') {
        $promo_id = (int) $old_order['promo_id'];
    }

    if ($promo_id !== null) {
        $insert_order = $db->prepare("
            INSERT INTO `Orders`
            (`restaurant_id`, `user_id`, `address_id`, `promo_id`, `order_type`, `total_price`, `order_status`, `payment_method`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$insert_order) {
            return false;
        }

        $insert_order->bind_param(
            "iiiisdss",
            $restaurant_id,
            $user_id,
            $address_id,
            $promo_id,
            $order_type,
            $total_price,
            $order_status,
            $payment_method
        );
    } else {
        $insert_order = $db->prepare("
            INSERT INTO `Orders`
            (`restaurant_id`, `user_id`, `address_id`, `order_type`, `total_price`, `order_status`, `payment_method`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$insert_order) {
            return false;
        }

        $insert_order->bind_param(
            "iiisdss",
            $restaurant_id,
            $user_id,
            $address_id,
            $order_type,
            $total_price,
            $order_status,
            $payment_method
        );
    }

    if (!$insert_order->execute()) {
        $insert_order->close();
        return false;
    }

    $new_order_id = (int) $insert_order->insert_id;
    $insert_order->close();

    $items = feedme_get_order_items($old_order_id);

    if (!empty($items)) {
        $insert_item = $db->prepare("
            INSERT INTO `Order Items`
            (`order_id`, `menu_item_id`, `quantity`, `price`)
            VALUES (?, ?, ?, ?)
        ");

        if ($insert_item) {
            foreach ($items as $item) {
                $menu_item_id = (int) $item['menu_item_id'];
                $quantity     = (int) $item['quantity'];
                $price        = (float) $item['price'];

                $insert_item->bind_param("iiid", $new_order_id, $menu_item_id, $quantity, $price);
                $insert_item->execute();
            }

            $insert_item->close();
        }
    }

    return $new_order_id;
}

/* =========================
   ORDERS SHORTCODE
========================= */
function feedme_orders_shortcode() {
    if (empty($_SESSION['user_id'])) {
        return '
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
            .feedme-card {
              background: #fff;
              border: 1px solid #dfe7f5;
              border-radius: 18px;
              padding: 24px;
              box-shadow: 0 8px 18px rgba(0,0,0,0.06);
            }
            .feedme-card a {
              color: #012458;
              font-weight: bold;
            }
          </style>
          <div class="feedme-wrap">
            <div class="feedme-card">
              <p>Please <a href="' . esc_url(feedme_login_url()) . '">log in</a> to view your orders.</p>
            </div>
          </div>
        </section>';
    }

    $db = feedme_db();
    if (!$db) {
        return '<p>Unable to connect to the database.</p>';
    }

    $user_id = (int) $_SESSION['user_id'];
    $message = '';

    if (isset($_POST['feedme_reorder_submit'])) {
        $old_order_id = (int) ($_POST['reorder_order_id'] ?? 0);
        $new_order_id = feedme_reorder_order($old_order_id, $user_id);

        if ($new_order_id) {
            $message = '✔ Order reordered successfully. New Order ID: ' . $new_order_id;
        } else {
            $message = '✖ Unable to reorder that order.';
        }
    }

    $sql = "
        SELECT o.*, r.name AS restaurant_name
        FROM `Orders` o
        LEFT JOIN `Restaurants` r ON o.restaurant_id = r.restaurant_id
        WHERE o.user_id = ?
        ORDER BY o.order_id DESC
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return '<p>Unable to load orders.</p>';
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }

    $stmt->close();

    $current_order = null;
    $previous_orders = [];

    if (!empty($orders)) {
        $current_order = $orders[0];
        if (count($orders) > 1) {
            $previous_orders = array_slice($orders, 1);
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

        .feedme-orders-hero {
          background: linear-gradient(135deg, #012458 0%, #023a7a 100%);
          border-radius: 20px;
          padding: 45px 35px;
          color: #fff;
          box-shadow: 0 12px 28px rgba(0,0,0,0.12);
          margin-bottom: 24px;
        }

        .feedme-orders-hero h1 {
          margin: 0 0 12px;
          font-size: 2.5rem;
          color: #fff;
        }

        .feedme-orders-hero p {
          margin: 0;
          font-size: 1.05rem;
          line-height: 1.7;
          max-width: 760px;
          color: #e9f1ff;
        }

        .feedme-order-card {
          background: #fff;
          border: 1px solid #dfe7f5;
          border-radius: 18px;
          padding: 22px;
          box-shadow: 0 8px 18px rgba(0,0,0,0.06);
          margin-bottom: 18px;
        }

        .feedme-order-card h2,
        .feedme-order-card h3 {
          margin-top: 0;
          color: #012458;
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

        .feedme-btn {
          background: #05f040;
          color: #012458;
          border: none;
          padding: 10px 14px;
          border-radius: 10px;
          font-weight: bold;
          cursor: pointer;
          margin-top: 12px;
        }

        .feedme-message {
          max-width: 1100px;
          margin: 0 auto 20px;
          padding: 14px 16px;
          border-radius: 12px;
          font-weight: bold;
          background: #fff4e8;
          color: #9a5a00;
          border: 1px solid #f0cf9c;
        }

        .feedme-message.success {
          background: #e8fff0;
          color: #11612e;
          border: 1px solid #bdeccc;
        }
      </style>

      <div class="feedme-wrap">
        <div class="feedme-orders-hero">
          <h1>My Orders</h1>
          <p>Track your latest order, review previous orders, and quickly reorder meals you have enjoyed before.</p>
        </div>

        <?php if ($message): ?>
          <div class="feedme-message <?php echo (strpos($message, '✔') !== false) ? 'success' : ''; ?>">
            <?php echo esc_html($message); ?>
          </div>
        <?php endif; ?>

        <div class="feedme-order-card">
          <h2>Current Order</h2>

          <?php if ($current_order): ?>
            <h3>Order #<?php echo esc_html($current_order['order_id']); ?></h3>
            <p><strong>Restaurant:</strong> <?php echo esc_html($current_order['restaurant_name'] ?: 'Unknown Restaurant'); ?></p>
            <p><strong>Order Type:</strong> <?php echo esc_html($current_order['order_type']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo esc_html($current_order['payment_method']); ?></p>
            <p><strong>Status:</strong> <?php echo esc_html($current_order['order_status']); ?></p>
            <p><strong>Total Price:</strong> $<?php echo esc_html(number_format((float) $current_order['total_price'], 2)); ?></p>

            <?php $items = feedme_get_order_items((int) $current_order['order_id']); ?>
            <?php if (!empty($items)): ?>
              <div class="feedme-item-list">
                <p><strong>Items:</strong></p>
                <?php foreach ($items as $item): ?>
                  <div class="feedme-item-row">
                    <?php echo esc_html($item['item_name'] ?: ('Menu Item #' . $item['menu_item_id'])); ?>
                    — Qty: <?php echo esc_html($item['quantity']); ?>
                    — $<?php echo esc_html(number_format((float) $item['price'], 2)); ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <p>No current order found.</p>
          <?php endif; ?>
        </div>

        <div class="feedme-order-card">
          <h2>Previous Orders</h2>

          <?php if (!empty($previous_orders)): ?>
            <?php foreach ($previous_orders as $order): ?>
              <div style="border-top:1px solid #e6edf8;padding-top:18px;margin-top:18px;">
                <h3>Order #<?php echo esc_html($order['order_id']); ?></h3>
                <p><strong>Restaurant:</strong> <?php echo esc_html($order['restaurant_name'] ?: 'Unknown Restaurant'); ?></p>
                <p><strong>Order Type:</strong> <?php echo esc_html($order['order_type']); ?></p>
                <p><strong>Payment Method:</strong> <?php echo esc_html($order['payment_method']); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html($order['order_status']); ?></p>
                <p><strong>Total Price:</strong> $<?php echo esc_html(number_format((float) $order['total_price'], 2)); ?></p>

                <?php $items = feedme_get_order_items((int) $order['order_id']); ?>
                <?php if (!empty($items)): ?>
                  <div class="feedme-item-list">
                    <p><strong>Items:</strong></p>
                    <?php foreach ($items as $item): ?>
                      <div class="feedme-item-row">
                        <?php echo esc_html($item['item_name'] ?: ('Menu Item #' . $item['menu_item_id'])); ?>
                        — Qty: <?php echo esc_html($item['quantity']); ?>
                        — $<?php echo esc_html(number_format((float) $item['price'], 2)); ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <form method="post">
                  <input type="hidden" name="reorder_order_id" value="<?php echo esc_attr($order['order_id']); ?>">
                  <button class="feedme-btn" type="submit" name="feedme_reorder_submit">Reorder Previous Order</button>
                </form>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No previous orders found.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('feedme_orders', 'feedme_orders_shortcode');

/* =========================
   CHECKOUT / ADDRESS HELPERS
========================= */
function feedme_find_or_create_address($building_number, $street_name, $suburb_city, $postcode, $user_id = 0) {
    $db = feedme_db();
    if (!$db) {
        return 0;
    }

    $building_number = trim($building_number);
    $street_name = trim($street_name);
    $suburb_city = trim($suburb_city);
    $postcode = (int) $postcode;

    if ($building_number === '' || $street_name === '' || $suburb_city === '' || $postcode <= 0) {
        return 0;
    }

    $find = $db->prepare("
        SELECT address_id
        FROM `Address`
        WHERE building_number = ?
          AND street_name = ?
          AND suburb_city = ?
          AND postcode = ?
        LIMIT 1
    ");

    if (!$find) {
        return 0;
    }

    $find->bind_param("sssi", $building_number, $street_name, $suburb_city, $postcode);
    $find->execute();
    $result = $find->get_result();
    $existing = $result ? $result->fetch_assoc() : null;
    $find->close();

    if ($existing) {
        $address_id = (int) $existing['address_id'];
    } else {
        $insert = $db->prepare("
            INSERT INTO `Address` (`building_number`, `street_name`, `suburb_city`, `postcode`)
            VALUES (?, ?, ?, ?)
        ");

        if (!$insert) {
            return 0;
        }

        $insert->bind_param("sssi", $building_number, $street_name, $suburb_city, $postcode);

        if (!$insert->execute()) {
            $insert->close();
            return 0;
        }

        $address_id = (int) $insert->insert_id;
        $insert->close();
    }

    if ($user_id > 0) {
        $check_link = $db->prepare("
            SELECT 1
            FROM `User Address`
            WHERE user_id = ? AND address_id = ?
            LIMIT 1
        ");

        if ($check_link) {
            $check_link->bind_param("ii", $user_id, $address_id);
            $check_link->execute();
            $link_result = $check_link->get_result();
            $has_link = $link_result ? $link_result->fetch_assoc() : null;
            $check_link->close();

            if (!$has_link) {
                $insert_link = $db->prepare("
                    INSERT INTO `User Address` (`user_id`, `address_id`)
                    VALUES (?, ?)
                ");

                if ($insert_link) {
                    $insert_link->bind_param("ii", $user_id, $address_id);
                    $insert_link->execute();
                    $insert_link->close();
                }
            }
        }
    }

    return $address_id;
}

/* =========================
   SUBMIT ORDER
========================= */
function feedme_submit_order_to_database($user_id, $restaurant_id, $address_data, $order_type, $payment_method, $total_price, $promo_code, $items) {
    $db = feedme_db();
    if (!$db) {
        return ['success' => false, 'message' => 'Database connection failed.'];
    }

    $user_id        = (int) $user_id;
    $restaurant_id  = (int) $restaurant_id;
    $order_type     = trim((string) $order_type);
    $payment_method = trim((string) $payment_method);
    $total_price    = (float) $total_price;
    $promo_code     = trim((string) $promo_code);

    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'User is not logged in.'];
    }

    if ($restaurant_id <= 0) {
        return ['success' => false, 'message' => 'Restaurant ID is missing.'];
    }

    if (empty($items) || !is_array($items)) {
        return ['success' => false, 'message' => 'Your cart is empty.'];
    }

    if ($payment_method === '') {
        return ['success' => false, 'message' => 'Payment method is required.'];
    }

    if ($order_type === '') {
        return ['success' => false, 'message' => 'Order type is required.'];
    }

    $building_number = trim((string) ($address_data['building_number'] ?? ''));
    $street_name     = trim((string) ($address_data['street_name'] ?? ''));
    $suburb_city     = trim((string) ($address_data['suburb_city'] ?? ''));
    $postcode        = (int) ($address_data['postcode'] ?? 0);

    if ($building_number !== '' && $street_name !== '' && $suburb_city !== '' && $postcode > 0) {
        $address_id = feedme_find_or_create_address(
            $building_number,
            $street_name,
            $suburb_city,
            $postcode,
            $user_id
        );
    } else {
        $last_address = feedme_get_last_used_address($user_id);
        $address_id = $last_address ? (int) $last_address['address_id'] : 0;
    }

    if ($address_id <= 0) {
        return ['success' => false, 'message' => 'Address could not be saved or found.'];
    }

    $promo_id = null;

    if ($promo_code !== '') {
        $promo_stmt = $db->prepare("
            SELECT promo_id
            FROM `Promo Code`
            WHERE code = ?
            LIMIT 1
        ");

        if ($promo_stmt) {
            $promo_stmt->bind_param("s", $promo_code);
            $promo_stmt->execute();
            $promo_result = $promo_stmt->get_result();
            $promo_row = $promo_result ? $promo_result->fetch_assoc() : null;
            $promo_stmt->close();

            if ($promo_row && !empty($promo_row['promo_id'])) {
                $promo_id = (int) $promo_row['promo_id'];
            }
        }
    }

    $order_status = 'Pending';

    $db->begin_transaction();

    try {
        if ($promo_id !== null) {
            $insert_order = $db->prepare("
                INSERT INTO `Orders`
                (`restaurant_id`, `user_id`, `address_id`, `promo_id`, `order_type`, `total_price`, `order_status`, `payment_method`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$insert_order) {
                throw new Exception('Unable to prepare order insert.');
            }

            $insert_order->bind_param(
                "iiiisdss",
                $restaurant_id,
                $user_id,
                $address_id,
                $promo_id,
                $order_type,
                $total_price,
                $order_status,
                $payment_method
            );
        } else {
            $insert_order = $db->prepare("
                INSERT INTO `Orders`
                (`restaurant_id`, `user_id`, `address_id`, `order_type`, `total_price`, `order_status`, `payment_method`)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$insert_order) {
                throw new Exception('Unable to prepare order insert.');
            }

            $insert_order->bind_param(
                "iiisdss",
                $restaurant_id,
                $user_id,
                $address_id,
                $order_type,
                $total_price,
                $order_status,
                $payment_method
            );
        }

        if (!$insert_order->execute()) {
            throw new Exception('Order insert failed: ' . $insert_order->error);
        }

        $order_id = (int) $insert_order->insert_id;
        $insert_order->close();

        $insert_item = $db->prepare("
            INSERT INTO `Order Items`
            (`order_id`, `menu_id`, `quantity`, `price`)
            VALUES (?, ?, ?, ?)
        ");

        if (!$insert_item) {
            throw new Exception('Unable to prepare order items insert: ' . $db->error);
        }

        $inserted_any_items = false;

        foreach ($items as $item) {
            $menu_id  = (int) ($item['menu_item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $price    = (float) ($item['price'] ?? 0);

            if ($menu_id <= 0 || $quantity <= 0) {
                continue;
            }

            $insert_item->bind_param("iiid", $order_id, $menu_id, $quantity, $price);

            if (!$insert_item->execute()) {
                throw new Exception('Order item insert failed: ' . $insert_item->error);
            }

            $inserted_any_items = true;
        }

        $insert_item->close();

        if (!$inserted_any_items) {
            throw new Exception('No valid order items were inserted.');
        }

        $db->commit();

        return [
            'success' => true,
            'message' => 'Order placed successfully.',
            'order_id' => $order_id
        ];

    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/* =========================
   AJAX: SUBMIT ORDER
========================= */
add_action('wp_ajax_feedme_submit_order', 'feedme_submit_order_ajax');

function feedme_submit_order_ajax() {
    if (!session_id()) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        wp_send_json([
            'success' => false,
            'message' => 'Please log in before placing an order.'
        ]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        wp_send_json([
            'success' => false,
            'message' => 'Invalid request payload.'
        ]);
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];
    $restaurant_id = (int) ($payload['restaurant_id'] ?? 0);
    $address = $payload['address'] ?? [];
    $order_type = trim((string) ($payload['order_type'] ?? 'Delivery'));
    $payment_method = trim((string) ($payload['payment_method'] ?? ''));
    $total_price = (float) ($payload['total_price'] ?? 0);
    $promo_code = trim((string) ($payload['promo_code'] ?? ''));
    $items = $payload['items'] ?? [];

    $result = feedme_submit_order_to_database(
        $user_id,
        $restaurant_id,
        $address,
        $order_type,
        $payment_method,
        $total_price,
        $promo_code,
        $items
    );

    wp_send_json($result);
    exit;
}

/* =========================
   AJAX: LAST USED ADDRESS
========================= */
add_action('wp_ajax_feedme_get_last_address', 'feedme_get_last_address_ajax');
add_action('wp_ajax_nopriv_feedme_get_last_address', 'feedme_get_last_address_ajax');

function feedme_get_last_address_ajax() {
    if (!session_id()) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        wp_send_json([
            'success' => false,
            'message' => 'User is not logged in.',
            'debug' => 'SESSION user_id missing in get_last_address'
        ]);
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];
    $last_address = feedme_get_last_used_address($user_id);

    if (!$last_address) {
        wp_send_json([
            'success' => false,
            'message' => 'No saved address found.',
            'debug' => 'No address returned from feedme_get_last_used_address()'
        ]);
        exit;
    }

    wp_send_json([
        'success' => true,
        'data' => [
            'address_id'       => $last_address['address_id'] ?? '',
            'building_number'  => $last_address['building_number'] ?? '',
            'street_name'      => $last_address['street_name'] ?? '',
            'suburb_city'      => $last_address['suburb_city'] ?? '',
            'postcode'         => $last_address['postcode'] ?? ''
        ]
    ]);
    exit;
}

/* =========================
   AJAX: CHECKOUT CONTEXT
========================= */
add_action('wp_ajax_feedme_get_checkout_context', 'feedme_get_checkout_context_ajax');
add_action('wp_ajax_nopriv_feedme_get_checkout_context', 'feedme_get_checkout_context_ajax');

function feedme_get_checkout_context_ajax() {
    if (!session_id()) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        wp_send_json([
            'success' => true,
            'logged_in' => false,
            'user' => null,
            'address' => null,
            'debug' => 'SESSION user_id missing in checkout context'
        ]);
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];
    $profile = feedme_get_current_user_profile();
    $last_address = feedme_get_last_used_address($user_id);

    wp_send_json([
        'success' => true,
        'logged_in' => true,
        'user' => [
            'user_id'      => $profile['user_id'] ?? '',
            'first_name'   => $profile['first_name'] ?? '',
            'last_name'    => $profile['last_name'] ?? '',
            'email'        => $profile['email'] ?? '',
            'phone_number' => $profile['phone_number'] ?? ''
        ],
        'address' => $last_address ? [
            'address_id'      => $last_address['address_id'] ?? '',
            'building_number' => $last_address['building_number'] ?? '',
            'street_name'     => $last_address['street_name'] ?? '',
            'suburb_city'     => $last_address['suburb_city'] ?? '',
            'postcode'        => $last_address['postcode'] ?? ''
        ] : null
    ]);
    exit;
}