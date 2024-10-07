<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'data_beas.xlsx';
$sheetName = isset($_POST['sheet_name']) ? $_POST['sheet_name'] : '';
$searchAccount = isset($_POST['account_number']) ? trim($_POST['account_number']) : '';

// التحقق من إدخال رقم الحساب
if (empty($searchAccount)) {
    echo '<p>يرجى إدخال رقم المشترك.</p>';
    exit;
}

try {
    $spreadsheet = IOFactory::load($filePath);
    $allSheets = $spreadsheet->getSheetNames();
    $filteredData = [];
    $grandTotal = 0;

    // تحديد رقم العمود للمبالغ في كل ورقة
    $amountColumns = [
        'السكني' => 9, // العمود 10
        'العمل' => 7,   // العمود 8
        'البرادات' => 5, // العمود 6
        'المعامل والمغاسل' => 7, // العمود 8
        'التجاري' => 9  // العمود 10
    ];

    if ($sheetName === 'all') {
        foreach ($allSheets as $sheetName) {
            if (!isset($amountColumns[$sheetName])) {
                continue; // تخطي الورقة إذا لم تكن ضمن الأوراق المحددة
            }
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $data = $sheet->toArray();
            $totalAmount = 0;

            $sheetFilteredData = [];

            foreach ($data as $i => $row) {
                if ($i === 0) continue; // تخطي العناوين
                if (isset($row[0]) && strval($row[0]) === strval($searchAccount)) {
                    $sheetFilteredData[] = $row;
                    $amountColumnIndex = $amountColumns[$sheetName];

                    if (isset($row[$amountColumnIndex]) && is_numeric($row[$amountColumnIndex])) {
                        $totalAmount += $row[$amountColumnIndex];
                        $grandTotal += $row[$amountColumnIndex];
                    }
                }
            }

            if (!empty($sheetFilteredData)) {
                // حفظ البيانات المفلترة لكل ورقة
                $filteredData[] = [
                    'sheet' => $sheetName,
                    'data' => $sheetFilteredData,
                    'headers' => $data[0], // العناوين الأصلية
                    'totalAmount' => $totalAmount
                ];
            }
        }

        if ($grandTotal > 0) {
            echo '<p style="font-size: 18px; font-weight: bold; color: #28a745;">المجموع الإجمالي لجميع الفواتير للحساب ' . htmlspecialchars($searchAccount) . ' هو: <span style="color: #dc3545;">' . htmlspecialchars($grandTotal) . '</span></p>';
        }
    } else {
        if (!isset($amountColumns[$sheetName])) {
            echo "<p>الورقة المطلوبة ($sheetName) غير موجودة.</p>";
            exit;
        }

        if (!$spreadsheet->sheetNameExists($sheetName)) {
            echo "<p>الورقة المطلوبة ($sheetName) غير موجودة.</p>";
            exit;
        }

        $sheet = $spreadsheet->getSheetByName($sheetName);
        $data = $sheet->toArray();
        $totalAmount = 0;

        $sheetFilteredData = [];

        foreach ($data as $i => $row) {
            if ($i === 0) continue; // تخطي العناوين
            if (isset($row[0]) && strval($row[0]) === strval($searchAccount)) {
                $sheetFilteredData[] = $row;
                $amountColumnIndex = $amountColumns[$sheetName];

                if (isset($row[$amountColumnIndex]) && is_numeric($row[$amountColumnIndex])) {
                    $totalAmount += $row[$amountColumnIndex];
                    $grandTotal += $row[$amountColumnIndex];
                }
            }
        }

        if (!empty($sheetFilteredData)) {
            // حفظ البيانات المفلترة للورقة المحددة
            $filteredData[] = [
                'sheet' => $sheetName,
                'data' => $sheetFilteredData,
                'headers' => $data[0], // العناوين الأصلية
                'totalAmount' => $totalAmount
            ];
        }

        if ($grandTotal > 0) {
            echo '<p style="font-size: 18px; font-weight: bold; color: #28a745;">المجموع الإجمالي لجميع الفواتير للحساب ' . htmlspecialchars($searchAccount) . ' هو: <span style="color: #dc3545;">' . htmlspecialchars($grandTotal) . '</span></p>';
        }
    }

    if (!empty($filteredData)) {
        foreach ($filteredData as $entry) {
            echo '<h3>فواتير: ' . htmlspecialchars($entry['sheet']) . '</h3>';
            echo '<div style="overflow-x:auto;">'; // إضافة التفاف الجدول داخل div لتفعيل التمرير الأفقي
            echo '<table border="1" style="width:100%; text-align:right; border-collapse:collapse;">';
            echo '<thead>';
            echo '<tr>';
            foreach ($entry['headers'] as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($entry['data'] as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars($cell ?? '') . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>'; // إنهاء div الذي يحتوي على الجدول
            if ($entry['totalAmount'] > 0) {
                echo '<p>المجموع في هذه الورقة هو: ' . htmlspecialchars($entry['totalAmount']) . '</p>';
            }
        }
    } else {
        echo '<p>لا توجد نتائج مطابقة لبحثك.</p>';
    }
} catch (Exception $e) {
    echo '<p>حدث خطأ أثناء قراءة ملف Excel: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>