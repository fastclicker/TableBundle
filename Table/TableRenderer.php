<?php

namespace PZAD\TableBundle\Table;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use PZAD\TableBundle\Table\Row\Row;
use PZAD\TableBundle\Table\Column\ColumnInterface;

/**
 * Renderer for a table.
 */
class TableRenderer
{	
	/**
	 * Container.
	 * 
	 * @var ContainerInterface
	 */
	protected $container;
	
	/**
	 * Request.
	 * 
	 * @var Request 
	 */
	protected $request;
	
	/**
	 * Router.
	 * 
	 * @var RouterInterface
	 */
	protected $router;

	function __construct(ContainerInterface $container, Request $request, RouterInterface $router)
	{
		$this->container	= $container;
		$this->request		= $request;
		$this->router		= $router;
	}
	
	/**
	 * Render the complete table.
	 * 
	 * @return string HTML code.
	 */
	public function render(TableView $tableView)
	{
		return sprintf("%s\n %s\n %s\n %s\n %s",
			$this->renderBegin($tableView),
			$this->renderHead($tableView),
			$this->renderBody($tableView),
			$this->renderEnd(),
			$this->renderPagination($tableView)				
		);
	}
	
	/**
	 * Render the table begin (<table> tag)
	 * 
	 * @param $tableView TableView	View of the table.
	 * 
	 * @return string HTML Code.
	 */
	public function renderBegin(TableView $tableView)
	{
		return sprintf(
			"<table id=\"%s\"%s>",
			$tableView->getName(),
			$this->renderAttributesContent($tableView->getAttributes())
		);
	}
	
	/**
	 * Render the table head.
	 * 
	 * @param $tableView TableView	View of the table.	
	 * 
	 * @return string HTML Code.
	 */
	public function renderHead(TableView $tableView)
	{		
		$content = "<thead>";
		$content .= sprintf("<tr%s>", $this->renderAttributesContent($tableView->getHeadAttributes()));
		
		foreach($tableView->getColumns() as $column)
		{
			/* @var $column ColumnInterface */

			// Render table column head with attributes for the head column
			// and a link for sortable columns.
			$content .= sprintf(
				"<th%s>%s</th>",
				$this->renderAttributesContent($column->getHeadAttributes()),
				$this->renderSortableColumnHeader($tableView, $column)
			);
			
			// Set the container, if the column need one.
			$containerSetter = array($column, 'setContainer');
			if(is_callable($containerSetter))
			{
				$column->setContainer($this->container);
			}
		}
		
		$content .= "</tr>";
		$content .= "</thead>";
		
		return $content;
	}
	
	/**
	 * Render the table body.
	 * 
	 * @param $tableView TableView	View of the table.
	 * 
	 * @return string HTML Code.
	 */
	public function renderBody(TableView $tableView)
	{
		$content = "<tbody>";
		
		foreach($tableView->getRows() as $row)
		{
			/* @var $row Row */
			
			$tr = "";
			foreach($tableView->getColumns() as $column)
			{
				/* @var $column ColumnInterface */
							
				$tr .= sprintf(
					"<td%s>%s</td>",
					$this->renderAttributesContent($column->getAttributes()),
					$column->getContent($row)
				);
			}
			
			$content .= sprintf("<tr%s>%s</tr>", $this->renderAttributesContent($row->getAttributes()), $tr);
		}
		
		if(count($tableView->getRows()) === 0)
		{
			$content .= sprintf(
				"<tr><td colspan=\"%s\">%s</td></tr>",
				count($tableView->getColumns()),
				$tableView->getEmptyValue()
			);
		}
		
		$content .= "</tbody>";
		
		return $content;
	}
	
	/**
	 * Render the table end (</table>).
	 * 
	 * @return string HTML Code.
	 */
	public function renderEnd()
	{
		return "</table>";
	}
	
	/**
	 * Render the pagination.
	 * 
	 * @param $tableView TableView	View of the table.
	 * 
	 * @return string HTML Code.
	 */
	public function renderPagination(TableView $tableView)
	{
		$pagination = $tableView->getPagination();
		
		if(!is_array($pagination) || count($pagination) === 0)
		{
			return;
		}
		
		if($pagination['count_pages'] < 2)
		{
			return;
		}
		
		$routeName = $this->request->get('_route');
		
		$ulClass = $pagination['ul_class'] === null ? "" : sprintf(" class=\"%s\"", $pagination['ul_class']);
		$content = sprintf("<ul%s>", $ulClass);
		
		// Left arrow.
		if($pagination['page'] == 0)
		{
			$liClass = "";
			if($pagination['li_class'] !== null || $pagination['li_class_disabled'] !== null)
			{
				$liClass = sprintf(" class=\"%s %s\"", $pagination['li_class'], $pagination['li_class_disabled']);
			}
			$content .= sprintf("<li%s><a>&laquo;</a></li>", $liClass);
		}
		else
		{
			$liClass = "";
			if($pagination['li_class'] !== null)
			{
				$liClass = sprintf(" class=\"%s\"", $pagination['li_class']);
			}
			
			$content .= sprintf(
				"<li%s><a href=\"%s\">&laquo;</a></li>",
				$liClass,
				$this->generateUrl(array(
					$pagination['param'] => $pagination['page']
				))
			);
		}
		
		// Pages
		for($page = 0; $page < $pagination['count_pages']; $page++)
		{
			$liClass = "";
			if($pagination['li_class'] !== null || ($page == $pagination['page'] && $pagination['li_class_active'] !== null))
			{
				$liClass = sprintf(" class=\"%s %s\"", $pagination['li_class'], $page == $pagination['page'] ? $pagination['li_class_active'] : '');
			}
			$content .= sprintf(
				"<li%s><a href=\"%s\">%s</a></li>",
				$liClass,
				$this->generateUrl(array(
					$pagination['param'] => $page + 1
				)),
				$page + 1
			);
		}
		
		// Right arrow.
		if($pagination['page'] == $pagination['count_pages'] - 1)
		{
			$liClass = "";
			if($pagination['li_class'] !== null || $pagination['li_class_disabled'] !== null)
			{
				$liClass = sprintf(" class=\"%s %s\"", $pagination['li_class'], $pagination['li_class_disabled']);
			}
			$content .= sprintf("<li%s><a>&raquo;</a></li>", $liClass);
		}
		else
		{
			$liClass = "";
			if($pagination['li_class'] !== null)
			{
				$liClass = sprintf(" class=\"%s\"", $pagination['li_class']);
			}
			$content .= sprintf(
				"<li%s><a href=\"%s\">&raquo;</a></li>",
				$liClass,
				$this->generateUrl(array(
					$pagination['param'] => $pagination['page'] + 2
				))
			);
		}
		
		$content .= "</ul>";
		
		return $content;
	}
	
	/**
	 * Reneres the header of a column with the sort-arrow-class,
	 * if the table is sortable and the column is the sortet column.
	 * 
	 * @param $tableView TableView	View of the table.
	 * @param $column	 Column		Column to be rendered.
	 * @return string				HTML Code
	 */
	private function renderSortableColumnHeader(TableView $tableView, ColumnInterface $column)
	{
		$sortable = $tableView->getSortable();
		
		if(!$column->isSortable() || !is_array($sortable) || count($sortable) === 0)
		{
			return $column->getLabel();
		}
		
		$isSortedColumn = $sortable['column'] == $column->getName() ? true : false;
		if($isSortedColumn)
		{
			$direction = $sortable['direction'] == 'asc' ? 'desc' : 'asc';
		}
		else
		{
			$direction = $sortable['empty_direction'];
		}
		
		$routeParams = array(
			$sortable['param_column'] => $column->getName(),
			$sortable['param_direction'] => $direction
		);
		
		$pagination = $tableView->getPagination();
		if($pagination !== null && count($pagination) > 0)
		{
			$routeParams[$pagination['param']] = 1;
		}

		return sprintf(
			"<a href=\"%s\">%s</a> %s",
			$this->generateUrl($routeParams),
			$column->getLabel(),
			$isSortedColumn ? sprintf("<span class=\"%s\"></span>", $sortable[sprintf('class_%s', $sortable['direction'])]) : ''
		);
	}
	
	/**
	 * Renders an array of attributes.
	 * 
	 * @param array $attributes Array of attributes.
	 * 
	 * @return string HTML Code of rendered attributes array.
	 */
	private function renderAttributesContent($attributes)
	{
		if(!is_array($attributes))
		{
			return "";
		}
		
		$content = "";
		foreach($attributes as $attributeName => $attributeValue)
		{
			$content .= sprintf(" %s=\"%s\"", $attributeName, $attributeValue);
		}
		
		return $content;
	}
	
	/**
	 * Generates an url, considering the current parameters of the route.
	 * 
	 * @param array		$routeParams	Parameters.
	 * @return string					HTML Code.
	 */
	private function generateUrl(array $routeParams)
	{
		$routeName = $this->request->get('_route');
		$currentRouteParams = array_merge(
			$this->request->attributes->get('_route_params'),
			$this->request->query->all()
		);

		foreach($routeParams as $paramName => $paramValue)
		{
			$currentRouteParams[$paramName] = $paramValue;
		}
		
		return $this->router->generate($routeName, $currentRouteParams);
	}
}
