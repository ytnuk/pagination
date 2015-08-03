<?php
namespace Ytnuk\Pagination;

use Countable;
use Iterator;
use Nette;
use Traversable;
use Ytnuk;

/**
 * Class Control
 *
 * @package Ytnuk\Pagination
 */
class Control
	extends Ytnuk\Application\Control
	implements Iterator, Countable
{

	/**
	 * @var int
	 * @persistent
	 */
	public $page = 1;

	/**
	 * @var int
	 */
	private $iterator;

	/**
	 * @var Traversable
	 */
	private $collection;

	/**
	 * @var Nette\Utils\Paginator
	 */
	private $paginator;

	/**
	 * @param Traversable $collection
	 * @param int $itemsPerPage
	 */
	public function __construct(
		Traversable $collection,
		$itemsPerPage = 1
	) {
		parent::__construct();
		$this->collection = $collection;
		$this->paginator = new Nette\Utils\Paginator;
		$this->paginator->setBase($this->page);
		$this->paginator->setItemsPerPage($itemsPerPage);
		if ($collection instanceof Countable) {
			$this->paginator->setItemCount($this->count($collection));
		}
		$this->rewind();
	}

	/**
	 * @inheritdoc
	 *
	 * @param Countable|NULL $collection
	 */
	public function count(Countable $collection = NULL)
	{
		return $collection ? count($collection) : $this->paginator->getPageCount();
	}

	/**
	 * @inheritdoc
	 */
	public function handleRedirect($fragment = NULL)
	{
		if ($this->getPresenter()->isAjax()) {
			$parent = $this->lookup(
				Nette\Application\UI\IRenderable::class,
				FALSE
			);
			if ($parent && $parent instanceof Nette\Application\UI\IRenderable) {
				$parent->redrawControl();
			}
		}
		parent::handleRedirect($fragment);
	}

	/**
	 * @inheritdoc
	 */
	public function next()
	{
		$this->iterator++;
	}

	/**
	 * @inheritdoc
	 */
	public function key()
	{
		return $this->iterator;
	}

	/**
	 * @inheritdoc
	 */
	public function valid()
	{
		return $this->iterator <= $this->paginator->getLastPage();
	}

	/**
	 * @return array
	 */
	public function current()
	{
		$this->paginator->setPage($this->iterator);
		$collection = $this->getCollection();
		$this->paginator->setPage($this->page);

		return $collection;
	}

	/**
	 * @return array
	 */
	public function getCollection()
	{
		return array_slice(
			iterator_to_array($this->collection),
			$this->paginator->getOffset(),
			$this->paginator->getItemsPerPage(),
			TRUE
		);
	}

	/**
	 * @return Nette\Utils\Paginator
	 */
	public function getPaginator()
	{
		return $this->paginator;
	}

	/**
	 * @inheritdoc
	 */
	public function rewind()
	{
		$this->iterator = $this->paginator->getBase();
	}

	/**
	 * @inheritdoc
	 */
	public function getCacheKey()
	{
		return array_merge(
			parent::getCacheKey(),
			[
				$this->paginator->getBase(),
				$this->paginator->getPage(),
				$this->paginator->getItemsPerPage(),
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		$this->paginator->setPage($this->page);
	}

	/**
	 * @inheritDoc
	 */
	public function saveState(
		array & $params,
		$reflection = NULL
	) {
		$this->page = $this->paginator->getPage();
		parent::saveState(
			$params,
			$reflection
		);
	}

	/**
	 * @return array
	 */
	protected function startup()
	{
		return [
			'paginator' => $this->paginator,
		];
	}
}
