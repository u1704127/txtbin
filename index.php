<?php
include 'db.php';

$rows = [];
$message = "";
$passkeyId = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $key      = trim($_POST['key'] ?? '');
    $value    = trim($_POST['value'] ?? '');

    if ($username !== "" && $key !== "") {

        $hashedKey = md5($key);

        // Validate username AND password
        $stmt = $conn->prepare("SELECT id FROM passkey_table WHERE username = ? AND passkey = ?");
        $stmt->bind_param("ss", $username, $hashedKey);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $passkeyId = $row['id'];

            // DELETE ALL (only user's own texts)
            if ($value === "<del />") {
                $delStmt = $conn->prepare("DELETE FROM value_table WHERE passkey_id = ?");
                $delStmt->bind_param("i", $passkeyId);
                $delStmt->execute();
                //$message = "All your data deleted successfully.";
            }

            // FETCH DESC (only user's own texts)
            elseif ($value === "") {
                $fetch = $conn->prepare(
                    "SELECT key_value, createdAt FROM value_table WHERE passkey_id = ? ORDER BY id DESC"
                );
                $fetch->bind_param("i", $passkeyId);
                $fetch->execute();
                $fetchResult = $fetch->get_result();
                while ($r = $fetchResult->fetch_assoc()) {
                    $rows[] = $r;
                }
            }

            // INSERT (linked to user's passkey_id)
            else {
                $insert = $conn->prepare(
                    "INSERT INTO value_table (passkey_id, key_value) VALUES (?, ?)"
                );
                $insert->bind_param("is", $passkeyId, $value);
                $insert->execute();
               // $message = "Data saved successfully.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Secure Text Vault</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .modal {
      display: none;
      position: fixed;
      z-index: 50;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4">

  <!-- Card -->
  <div class="w-full max-w-2xl bg-white rounded-lg shadow-sm border p-6">

    <!-- Header -->
    <h1 class="text-2xl font-semibold text-gray-900 mb-1">
      Recycle Text
    </h1>
    <p class="text-sm text-gray-500 mb-6">
       
    </p>

    <form method="POST" id="mainForm">

      <!-- Username -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Username
        </label>
        <input
          type="text"
          name="username"
          id="username"
          placeholder="Enter your username"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
        />
      </div>

      <!-- Passkey -->
      <div class="mb-4">
        <label class="flex justify-between items-center text-sm font-medium text-gray-700 mb-1">
          <span>Passkey</span>
          <button
            type="button"
            id="lockToggle"
            onclick="toggleLock()"
            class="text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300 transition"
          >
            🔓 Lock
          </button>
        </label>
        <input
          type="password"
          name="key"
          id="passkey"
          placeholder="••••••••"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
        />
      </div>

      <!-- Value -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Value
        </label>
        <textarea
          name="value"
          placeholder=""
          class="w-full min-h-[120px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
        ></textarea>
      </div>

      <!-- Submit -->
      <button
        type="submit"
        class="w-full mt-4 bg-black text-white py-2 rounded-md text-sm font-medium hover:bg-gray-900 transition"
      >
        Submit
      </button>
    </form>

    <!-- Registration Button -->
    <button
      onclick="openModal()"
      type="button"
      class="w-full mt-3 bg-gray-200 text-gray-800 py-2 rounded-md text-sm font-medium hover:bg-gray-300 transition"
    >
      Get your username and password
    </button>

    <!-- Message -->
    <?php if ($message): ?>
      <p class="mt-4 text-sm text-center text-green-600">
        <?= htmlspecialchars($message) ?>
      </p>
    <?php endif; ?>

    <!-- Data Table -->
    <?php if (!empty($rows)): ?>
      <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm border border-gray-200 rounded-md">
          <thead class="bg-gray-100">
            <tr>
              <th class="text-left px-3 py-2 border-b">Data</th>
              <th class="text-left px-3 py-2 border-b">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr class="border-t">
                <td class="px-3 py-2">
                  <?= nl2br($row['key_value']); ?>
                </td>
                <td class="w-1/3 px-1 py-2 border-b text-gray-500 whitespace-nowrap">
                  <?= $row['createdAt']; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>

  <!-- Registration Modal -->
  <div id="registrationModal" class="modal">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Create Account</h2>
      
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Username
        </label>
        <input
          type="text"
          id="regUsername"
          placeholder="Choose a username"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
        />
        <p id="usernameError" class="text-xs text-red-600 mt-1 hidden"></p>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Password
        </label>
        <input
          type="password"
          id="regPassword"
          placeholder="Choose a password"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
        />
      </div>

      <p id="regMessage" class="text-sm mb-4 hidden"></p>

      <div class="flex gap-2">
        <button
          onclick="registerUser()"
          class="flex-1 bg-black text-white py-2 rounded-md text-sm font-medium hover:bg-gray-900 transition"
        >
          Register
        </button>
        <button
          onclick="closeModal()"
          class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-md text-sm font-medium hover:bg-gray-300 transition"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>

  <script>
    let isLocked = false;

    function toggleLock() {
      const usernameField = document.getElementById('username');
      const passkeyField = document.getElementById('passkey');
      const lockBtn = document.getElementById('lockToggle');

      isLocked = !isLocked;

      if (isLocked) {
        usernameField.type = 'password';
        usernameField.disabled = true;
        passkeyField.disabled = true;
        lockBtn.textContent = '🔒 Unlock';
        lockBtn.classList.remove('bg-gray-200', 'hover:bg-gray-300');
        lockBtn.classList.add('bg-green-200', 'hover:bg-green-300');
      } else {
        usernameField.type = 'text';
        usernameField.disabled = false;
        passkeyField.disabled = false;
        lockBtn.textContent = '🔓 Lock';
        lockBtn.classList.remove('bg-green-200', 'hover:bg-green-300');
        lockBtn.classList.add('bg-gray-200', 'hover:bg-gray-300');
      }
    }

    // Re-enable fields before form submission to ensure values are sent
    document.getElementById('mainForm').addEventListener('submit', function() {
      if (isLocked) {
        document.getElementById('username').disabled = false;
        document.getElementById('passkey').disabled = false;
      }
    });

    function openModal() {
      document.getElementById('registrationModal').classList.add('active');
    }

    function closeModal() {
      document.getElementById('registrationModal').classList.remove('active');
      document.getElementById('regUsername').value = '';
      document.getElementById('regPassword').value = '';
      document.getElementById('usernameError').classList.add('hidden');
      document.getElementById('regMessage').classList.add('hidden');
    }

    async function registerUser() {
      const username = document.getElementById('regUsername').value.trim();
      const password = document.getElementById('regPassword').value.trim();
      const errorEl = document.getElementById('usernameError');
      const messageEl = document.getElementById('regMessage');

      errorEl.classList.add('hidden');
      messageEl.classList.add('hidden');

      if (!username || !password) {
        errorEl.textContent = 'Both fields are required';
        errorEl.classList.remove('hidden');
        return;
      }

      try {
        const response = await fetch('register.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        });

        const result = await response.json();

        if (result.success) {
          messageEl.textContent = result.message;
          messageEl.classList.remove('hidden');
          messageEl.classList.add('text-green-600');
          messageEl.classList.remove('text-red-600');
          setTimeout(() => {
            closeModal();
          }, 2000);
        } else {
          errorEl.textContent = result.message;
          errorEl.classList.remove('hidden');
        }
      } catch (error) {
        errorEl.textContent = 'Registration failed. Please try again.';
        errorEl.classList.remove('hidden');
      }
    }
  </script>

</body>
</html>
