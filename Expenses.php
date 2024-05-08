{source}
<?php
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task']) && $_POST['task'] == 'addExpense') {
    if (!JSession::checkToken()) {
        JFactory::getApplication()->enqueueMessage('Invalid token', 'error');
        return;
    }

    $input = JFactory::getApplication()->input;
    $expenseName = $input->getString('expenseName');
    $expenseCost = $input->getFloat('expenseCost');
    $expenseDate = $input->getString('expenseDate');
    $categoryID = $input->getInt('categoryID');
    $isRecurring = $input->getInt('isRecurring', 0);
    $recurrenceFrequency = $isRecurring ? $input->getString('recurrenceFrequency', 'monthly') : null;

    if ($expenseName != '' && $expenseCost > 0 && $expenseDate != '') {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $columns = ['ExpenseName', 'ExpenseCost', 'ExpenseDate', 'UserId', 'categoryID', 'isRecurring', 'recurrenceFrequency'];
        $values = [
            $db->quote($expenseName), 
            $db->quote($expenseCost), 
            $db->quote($expenseDate), 
            $db->quote(JFactory::getUser()->id), 
            $db->quote($categoryID),
            $db->quote($isRecurring), 
            $recurrenceFrequency ? $db->quote($recurrenceFrequency) : 'NULL'
        ];

        $query->insert($db->quoteName('kht_tblexpense'))
              ->columns($db->quoteName($columns))
              ->values(implode(',', $values));

        $db->setQuery($query);
        $db->execute();
        JFactory::getApplication()->enqueueMessage('Expense added successfully', 'success');
    } else {
        JFactory::getApplication()->enqueueMessage('Please fill in all required fields correctly.', 'error');
    }
}

// Fetch Expense Categories
$query = $db->getQuery(true);
$query->select($db->quoteName(['categoryID', 'name']));  // Adjusted to use 'categoryID'
$query->from($db->quoteName('kht_expense_categories'));
$db->setQuery($query);
$categories = $db->loadAssocList();
?>
<script>
function toggleRecurrenceInterval(isChecked) {
    var intervalDiv = document.getElementById('recurrenceInterval');
    intervalDiv.style.display = isChecked ? 'block' : 'none';
}
</script>
<form action="" method="post">
    <div class="form-group">
        <label for="expenseName">Expense Name:</label>
        <input type="text" class="form-control" id="expenseName" name="expenseName" required>
    </div>
    <div class="form-group">
        <label for="expenseCost">Expense Cost:</label>
        <input type="number" class="form-control" id="expenseCost" name="expenseCost" required>
    </div>
    <div class="form-group">
        <label for="expenseDate">Date:</label>
        <input type="date" class="form-control" id="expenseDate" name="expenseDate" required>
    </div>
    <div class="form-group">
        <label for="categoryID">Category:</label>
        <select class="form-control" id="categoryID" name="categoryID" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['categoryID']; ?>"><?php echo $category['name']; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="isRecurring">Recurring:</label>
        <input type="checkbox" id="isRecurring" name="isRecurring" value="1" onclick="toggleRecurrenceInterval(this.checked);">
    </div>
    <div class="form-group" id="recurrenceInterval" style="display: none;">
        <label for="recurrenceFrequency">Recurrence Frequency:</label>
        <select class="form-control" id="recurrenceFrequency" name="recurrenceFrequency">
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
        </select>
    </div>
    <input type="hidden" name="<?php echo JSession::getFormToken(); ?>" value="1">
    <input type="hidden" name="task" value="addExpense">
    <button type="submit" class="btn btn-primary">Submit</button>
</form>
{/source}
