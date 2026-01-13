<?php
include 'db.php';

$rows = [];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $key   = trim($_POST['key'] ?? '');
    $value = trim($_POST['value'] ?? '');

    if ($key !== "") {

        $hashedKey = md5($key);

        $stmt = $conn->prepare("SELECT id FROM passkey_table WHERE passkey = ?");
        $stmt->bind_param("s", $hashedKey);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            // DELETE ALL
            if ($value === "<del />") {
                $conn->query("DELETE FROM value_table");
                //$message = "All data deleted successfully.";
            }

            // FETCH DESC
            elseif ($value === "") {
                $fetch = $conn->query(
                    "SELECT key_value, createdAt FROM value_table ORDER BY id DESC"
                );
                while ($r = $fetch->fetch_assoc()) {
                    $rows[] = $r;
                }
            }

            // INSERT
            else {
                $insert = $conn->prepare(
                    "INSERT INTO value_table (key_value) VALUES (?)"
                );
                $insert->bind_param("s", $value);
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

    <form method="POST">

      <!-- Passkey -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Passkey
        </label>
        <input
          type="password"
          name="key"
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

</body>
</html>
