{source}<?php
defined('_JEXEC') or die('Restricted access');

// Load CSS and JavaScript
$document = JFactory::getDocument();
$document->addStyleSheet(JUri::root() . 'css/bootstrap.min.css');
$document->addStyleSheet(JUri::root() . 'css/font-awesome.min.css');
$document->addStyleSheet(JUri::root() . 'css/datepicker3.css');
$document->addStyleSheet(JUri::root() . 'css/styles.css');
$document->addScript(JUri::root() . 'js/jquery-1.11.1.min.js');
$document->addScript(JUri::root() . 'js/bootstrap.min.js');
$document->addScript(JUri::root() . 'js/chart.min.js');
$document->addScript(JUri::root() . 'js/easypiechart.js');
$document->addScript(JUri::root() . 'js/bootstrap-datepicker.js');

$user = JFactory::getUser();
$userId = $user->id;
$db = JFactory::getDbo();

// Handle report type and deletion form submission
$reportType = isset($_POST['reportType']) ? $_POST['reportType'] : 'daily';
$deleteDate = isset($_POST['deleteDate']) ? $_POST['deleteDate'] : '';

// Process deletion request
if (!empty($deleteDate)) {
    // Delete income records
    $queryDeleteIncome = $db->getQuery(true);
    $queryDeleteIncome->delete($db->quoteName('kht_tblincome'))
        ->where($db->quoteName('incomeDate') . ' < ' . $db->quote($deleteDate))
        ->where($db->quoteName('UserId') . ' = ' . $db->quote($userId));
    $db->setQuery($queryDeleteIncome);
    $db->execute();

    // Delete expense records
    $queryDeleteExpense = $db->getQuery(true);
    $queryDeleteExpense->delete($db->quoteName('kht_tblexpense'))
        ->where($db->quoteName('ExpenseDate') . ' < ' . $db->quote($deleteDate))
        ->where($db->quoteName('UserId') . ' = ' . $db->quote($userId));
    $db->setQuery($queryDeleteExpense);
    $db->execute();

    JFactory::getApplication()->enqueueMessage('Old records before ' . htmlspecialchars($deleteDate) . ' have been deleted.', 'success');
}

// Adjusting query based on the selection
$dateFormat = '%Y-%m-%d'; // Default daily
if ($reportType == 'monthly') {
    $dateFormat = '%Y-%m'; // Group by month
} elseif ($reportType == 'yearly') {
    $dateFormat = '%Y'; // Group by year
}

// Fetch data
$queryIncome = $db->getQuery(true);
$queryExpense = $db->getQuery(true);

// Income data
$queryIncome->select(array(
    "SUM(incomeAmount) AS totalIncome",
    "DATE_FORMAT(incomeDate, '$dateFormat') AS date",
    "isRecurringIncome AS recurring"
))
->from($db->quoteName('kht_tblincome'))
->where($db->quoteName('UserId') . ' = ' . $db->quote($userId))
->group("date, recurring");
$db->setQuery($queryIncome);
$incomes = $db->loadObjectList();

// Expense data
$queryExpense->select(array(
    "SUM(ExpenseCost) AS expenseTotal",
    "DATE_FORMAT(ExpenseDate, '$dateFormat') AS date",
    "isRecurring AS recurring"
))
->from($db->quoteName('kht_tblexpense'))
->where($db->quoteName('UserId') . ' = ' . $db->quote($userId))
->group("date, recurring");
$db->setQuery($queryExpense);
$expenses = $db->loadObjectList();

// Data arrays and future projections setup
$financialData = [];
$futureProjections = [];

// Process expenses and incomes for display and projections
foreach ($expenses as $expense) {
    $date = $expense->date;
    $isRecurring = $expense->recurring == 1;
    if (!isset($financialData[$date])) {
        $financialData[$date] = ['income' => 0, 'expenses' => $expense->expenseTotal];
        if ($isRecurring) {
            $futureProjections[$date]['expenses'] = $expense->expenseTotal;
        }
    } else {
        $financialData[$date]['expenses'] += $expense->expenseTotal;
        if ($isRecurring) {
            $futureProjections[$date]['expenses'] += $expense->expenseTotal;
        }
    }
}

foreach ($incomes as $income) {
    $date = $income->date;
    $isRecurring = $income->recurring == 1;
    if (!isset($financialData[$date])) {
        $financialData[$date] = ['income' => $income->totalIncome, 'expenses' => 0];
        if ($isRecurring) {
            $futureProjections[$date]['income'] = $income->totalIncome;
        }
    } else {
        $financialData[$date]['income'] += $income->totalIncome;
        if ($isRecurring) {
            $futureProjections[$date]['income'] += $income->totalIncome;
        }
    }
}

// Output section
echo '<div class="container-fluid">';
echo '<h1 class="page-header text-center">Financial Dashboard</h1>';
echo '<form action="" method="post" id="reportForm">';
echo '<div class="form-group">';
echo '<label for="reportType">View Report By:</label>';
echo '<select class="form-control" id="reportType" name="reportType" onchange="document.getElementById(\'reportForm\').submit();">';
echo '<option value="daily"' . ($reportType == 'daily' ? ' selected' : '') . '>Daily</option>';
echo '<option value="monthly"' . ($reportType == 'monthly' ? ' selected' : '') . '>Monthly</option>';
echo '<option value="yearly"' . ($reportType == 'yearly' ? ' selected' : '') . '>Yearly</option>';
echo '</select>';
echo '</div>';
echo '</form>';

// Output 2

// Financial data display
echo '<div class="row">';
foreach ($financialData as $date => $data) {
    $netBalance = $data['income'] - $data['expenses'];
    echo '<div class="col-xs-12 col-sm-6 col-md-4">';
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading"><h3 class="panel-title">' . htmlspecialchars($date) . ' Overview</h3></div>';
    echo '<div class="panel-body text-center">';
    echo '<p>Total Income: ' . htmlspecialchars(number_format($data['income'], 2)) . '</p>';
    echo '<p>Total Expenses: ' . htmlspecialchars(number_format($data['expenses'], 2)) . '</p>';
    echo '<p>Net Balance: ' . htmlspecialchars(number_format($netBalance, 2)) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';

// Future projections
echo '<div class="row">';
echo '<div class="col-xs-12">';
echo '<h2 class="sub-header text-center">Future Projections</h2>';
foreach ($futureProjections as $date => $projection) {
    echo '<div class="col-xs-12 col-sm-6 col-md-4">';
    echo '<div class="panel panel-info">';
    echo '<div class="panel-heading"><h3 class="panel-title">' . htmlspecialchars($date) . ' Projections</h3></div>';
    echo '<div class="panel-body text-center">';
    echo '<p>Projected Income: ' . htmlspecialchars(number_format($projection['income'] ?? 0, 2)) . '</p>';
    echo '<p>Projected Expenses: ' . htmlspecialchars(number_format($projection['expenses'] ?? 0, 2)) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';  // Close row for future projections

// Deletion form
echo '<form action="" method="post">';
echo '<div class="form-group">';
echo '<label for="deleteDate">Delete records older than:</label>';
echo '<input type="date" class="form-control" id="deleteDate" name="deleteDate" required>';
echo '<button type="submit" class="btn btn-danger" style="margin-top: 10px;">Delete Old Records</button>';
echo '</div>';
echo '</form>';

echo '</div>';  // Close container-fluid
?>

{/source}
