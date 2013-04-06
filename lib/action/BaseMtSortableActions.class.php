<?php

/**
 * BaseMtSortableActions
 *
 * @package mtDoctrineExtraPlugin
 *
 * @author Gábor Egyed <egyed.gabor@mentha.hu>
 */
abstract class BaseMtSortableActions extends sfActions
{
  /**
   * Gets all record from the table.
   *
   * @param sfWebRequest $request
   */
  public function executeSort(sfWebRequest $request)
  {
    $this->objects = $this->getTableForSorting()->queryOrdered()->execute();
  }

  /**
   * Sorts the records based on the "order" parameter.
   *
   * @param sfWebRequest $request
   *
   * @return string
   */
  public function executeOrder(sfWebRequest $request)
  {
    $this->forward404Unless($request->isXmlHttpRequest());
    $this->forward404Unless($order = $request->getParameter('order'));
    $this->forward404Unless(is_array($order));

    try
    {
      $request->checkCSRFProtection();
      $this->getTableForSorting()->updateOrder($order);
    }
    catch(Exception $e)
    {
      $this->logMessage($e->getMessage(), 'err');
    }

    /** @var $response sfWebResponse */
    $response =  $this->getResponse();
    $response->setContentType($request->getMimeType('json'));
    $response->setContent(json_encode(true));

    return sfView::NONE;
  }

  /**
   * Gets the sortable table.
   *
   * Tries to guess which table to use by searching
   * for a "model" parameter or option in the matched
   * route.
   *
   * @throws RuntimeException
   *
   * @return Doctrine_Table|mtSortable
   */
  protected function getTableForSorting()
  {
    if ($this->getRoute() instanceof sfDoctrineRoute)
    {
      $params = $this->getRoute()->getOptions();
    }
    else
    {
      $params = $this->getRoute()->getParameters();
    }

    if (!isset($params['model']))
    {
      throw new RuntimeException('The route should have a "model" parameter or option.');
    }

    $table = Doctrine_Core::getTable($params['model']);

    if (!$table->hasTemplate('mtSortable'))
    {
      throw new RuntimeException(sprintf('"%s" table is not mtSortable.', $params['model']));
    }

    return $table;
  }
}
