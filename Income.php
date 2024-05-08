{source}
<?php
defined('_JEXEC') or die('Restricted access');

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task']) && $_POST['task'] == 'addIncome') {
    if (!JSession::checkToken()) {
        JFactory::getApplication()->enqueueMessage('Invalid token', 'error');
        return;
    }

    $input = JFactory::getApplication()->input;
    $incomeAmount = $input->getFloat('incomeAmount');
    $incomeDate = $input->getString('incomeDate');
    $userId = JFactory::getUser()->id;

    if ($incomeAmount > 0 && $incomeDate != '') {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $columns = array('incomeAmount', 'incomeDate', 'UserId');
        $values = array($db->quote($incomeAmount), $db->quote($incomeDate), $userId);

        $query->insert($db->quoteName('kht_tblincome'))
              ->columns($db->quoteName($columns))
              ->values(implode(',', $values));

        $db->setQuery($query);

        try {
            $db->execute();
            JFactory::getApplication()->enqueueMessage('Income added successfully', 'success');
        } catch (RuntimeException $e) {
            JFactory::getApplication()->enqueueMessage('Database error: ' . $e->getMessage(), 'error');
        }
    } else {
        JFactory::getApplication()->enqueueMessage('Please fill in all required fields.', 'error');
    }
}
?>
<form action="" method="post">
    <div class="form-group">
        <label for="incomeAmount">Income Amount:</label>
        <input type="number" class="form-control" id="incomeAmount" name="incomeAmount" required>
    </div>
    <div class="form-group">
        <label for="incomeDate">Date of Income:</label>
        <input type="date" class="form-control" id="incomeDate" name="incomeDate" required>
    </div>
    <input type="hidden" name="<?php echo JSession::getFormToken(); ?>" value="1">
    <input type="hidden" name="task" value="addIncome">
    <button type="submit" class="btn btn-primary">Submit</button>
</form>
{/source}