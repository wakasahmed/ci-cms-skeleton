<?php defined('BASEPATH') OR exit('No direct script access allowed');

$userSelectName = isset($name) ? (string) $name : 'user_id';
$userSelectID = isset($id) ? (string) $id : 'user_id';
$userSelectUsers = isset($users) && is_array($users) ? $users : array();
$userSelectMultiple = !empty($multiple);
$userSelectValue = isset($selected_id) ? (int) $selected_id : 0;
$userSelectValues = isset($selected_ids) && is_array($selected_ids) ? array_map('intval', $selected_ids) : array();
$userSelectRequired = !empty($required);
$userSelectHelp = isset($help) ? (string) $help : '';
$userSelectEmptyLabel = isset($empty_label) ? (string) $empty_label : 'Select a user';
$userSelectAllLink = $userSelectMultiple && !empty($select_all);
$userSelectImageDirectory = isset($image_directory) ? trim((string) $image_directory, '/') : 'assets/frontend/images/admins';
?>
<select
    class="form-select select2 admin-user-select"
    name="<?php echo htmlspecialchars($userSelectName, ENT_QUOTES, 'UTF-8').($userSelectMultiple ? '[]' : ''); ?>"
    id="<?php echo htmlspecialchars($userSelectID, ENT_QUOTES, 'UTF-8'); ?>"
    data-user-select
    data-placeholder="<?php echo htmlspecialchars($userSelectEmptyLabel, ENT_QUOTES, 'UTF-8'); ?>"
    <?php echo $userSelectMultiple ? 'multiple' : ''; ?>
    <?php echo $userSelectRequired ? 'data-validate="required" required' : ''; ?>
>
    <?php if (!$userSelectMultiple) { ?><option value=""><?php echo htmlspecialchars($userSelectEmptyLabel, ENT_QUOTES, 'UTF-8'); ?></option><?php } ?>
    <?php foreach ($userSelectUsers as $user) { ?>
        <?php
        $userID = isset($user['id']) ? (int) $user['id'] : 0;
        $userName = isset($user['full_name']) ? trim((string) $user['full_name']) : '';
        $userName = $userName !== '' ? $userName : (isset($user['user_name']) ? (string) $user['user_name'] : 'User');
        $userEmail = isset($user['email']) ? trim((string) $user['email']) : '';
        $userPhone = isset($user['phone']) ? trim((string) $user['phone']) : '';
        $userAvatar = isset($user['avatar']) ? basename((string) $user['avatar']) : '';
        $userAvatarURL = $userAvatar !== '' ? base_url($userSelectImageDirectory.'/'.$userAvatar) : '';
        $userSelected = $userSelectMultiple ? in_array($userID, $userSelectValues, TRUE) : ($userSelectValue === $userID);
        ?>
        <option
            value="<?php echo $userID; ?>"
            data-user-name="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>"
            data-user-email="<?php echo htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8'); ?>"
            data-user-phone="<?php echo htmlspecialchars($userPhone, ENT_QUOTES, 'UTF-8'); ?>"
            data-user-avatar="<?php echo htmlspecialchars($userAvatarURL, ENT_QUOTES, 'UTF-8'); ?>"
            <?php echo $userSelected ? 'selected' : ''; ?>
        ><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></option>
    <?php } ?>
</select>
<?php if ($userSelectAllLink) { ?>
    <div class="admin-user-select-actions mt-1">
        <button
            type="button"
            class="btn btn-link btn-sm p-0"
            data-user-select-all="#<?php echo htmlspecialchars($userSelectID, ENT_QUOTES, 'UTF-8'); ?>"
        >Select all</button>
        <span class="text-muted" aria-hidden="true">&middot;</span>
        <button
            type="button"
            class="btn btn-link btn-sm p-0"
            data-user-select-clear="#<?php echo htmlspecialchars($userSelectID, ENT_QUOTES, 'UTF-8'); ?>"
        >Clear</button>
    </div>
<?php } ?>
<?php if ($userSelectHelp !== '') { ?>
    <div class="form-text"><?php echo htmlspecialchars($userSelectHelp, ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
