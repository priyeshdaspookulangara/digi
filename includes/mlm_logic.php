<?php

/**
 * Fetches dynamic settings from database.
 */
function getMlmSetting($pdo, $key, $default = null) {
    $stmt = $pdo->prepare("SELECT key_value FROM site_settings WHERE key_name = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['key_value'] : $default;
}

/**
 * Distributes the entry fee into predefined buckets.
 */
function distributeEntryFee($pdo, $user_id) {
    $entry_fee = (float)getMlmSetting($pdo, 'entry_fee', 3000.00);
    $level_comm = (float)getMlmSetting($pdo, 'level_commission', 100.00);

    // Distribution configuration (Example logic: split 1/6th to each bucket, 1/3rd to levels)
    $buckets = [
        'Burfee' => $entry_fee * (500/3000),
        'Level' => $level_comm * 10,
        'Royalty Chamber' => $entry_fee * (500/3000),
        'Platform Fee' => $entry_fee * (500/3000),
        'Reserve' => $entry_fee * (500/3000)
    ];

    foreach ($buckets as $bucket => $amount) {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, bucket, status) VALUES (?, ?, 'entry_fee', ?, 'completed')");
        $stmt->execute([$user_id, $amount, $bucket]);
    }

    // Now distribute the 'Level' bucket across 10 levels
    distributeLevelCommissions($pdo, $user_id, $level_comm);

    // Check for milestones/rebirth
    checkMilestones($pdo, $user_id);
}

/**
 * Handles the 10-level payout structure.
 */
function distributeLevelCommissions($pdo, $user_id, $amount_per_level) {
    $current_id = $user_id;
    $levels_paid = 0;

    for ($level = 1; $level <= 10; $level++) {
        // Find parent
        $stmt = $pdo->prepare("SELECT parent_id FROM mlm_hierarchy WHERE user_id = ?");
        $stmt->execute([$current_id]);
        $row = $stmt->fetch();

        if ($row && $row['parent_id']) {
            $parent_id = $row['parent_id'];

            // Pay commission to parent
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, bucket, status) VALUES (?, ?, 'commission', 'Level Payout', 'completed')");
            $stmt->execute([$parent_id, $amount_per_level]);

            $current_id = $parent_id;
            $levels_paid++;
        } else {
            // No more parents. Remaining commission goes to Admin (ID 1)
            $remaining_levels = 10 - $levels_paid;
            if ($remaining_levels > 0) {
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, bucket, status) VALUES (1, ?, 'commission', 'Level Payout (Surplus)', 'completed')");
                $stmt->execute([$remaining_levels * $amount_per_level]);
            }
            break;
        }
    }
}

/**
 * Checks for growth milestones and generates Rebirth IDs.
 */
function checkMilestones($pdo, $user_id) {
    // Basic Rebirth logic: If a user's downline (direct referrals) reaches a milestone
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referrer_id = ?");
    $stmt->execute([$user_id]);
    $referral_count = $stmt->fetchColumn();

    $milestone = (int)getMlmSetting($pdo, 'rebirth_milestone', 10);

    if ($referral_count > 0 && $referral_count % $milestone === 0) {
        generateRebirthID($pdo, $user_id);
    }
}

/**
 * Generates a "Basic Rebirth ID" for a user.
 */
function generateRebirthID($pdo, $user_id) {
    // Fetch user info
    $stmt = $pdo->prepare("SELECT username, email, referrer_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $rebirth_username = $user['username'] . "_RB" . time();
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, referrer_id, level) VALUES (?, 'REBIRTH_AUTO', ?, 'member', ?, 1)");
        $stmt->execute([$rebirth_username, "rb_" . time() . "_" . $user['email'], $user['referrer_id']]);

        $new_user_id = $pdo->lastInsertId();

        // Link in hierarchy under the same parent
        $stmt = $pdo->prepare("INSERT INTO mlm_hierarchy (user_id, parent_id, level_in_tree) VALUES (?, ?, 1)");
        $stmt->execute([$new_user_id, $user['referrer_id']]);

        // Increment rebirth count for original user
        $stmt = $pdo->prepare("UPDATE users SET rebirth_count = rebirth_count + 1 WHERE id = ?");
        $stmt->execute([$user_id]);

        // Log transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, bucket, status) VALUES (?, 0, 'rebirth', 'System Milestone', 'completed')");
        $stmt->execute([$user_id, 0]);
    }
}
?>
