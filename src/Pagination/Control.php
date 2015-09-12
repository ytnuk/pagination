<?php
namespace Ytnuk\Pagination;

use ArrayObject;
use Countable;
use Iterator;
use IteratorAggregate;
use Nette;
use Traversable;
use Ytnuk;

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

	public function __construct(
		Traversable $collection,
		int $itemsPerPage = 1,
		Nette\Utils\Paginator $paginator = NULL
	) {
		parent::__construct();
		$this->collection = $collection;
		$paginator = $paginator ? : new Nette\Utils\Paginator;
		$paginator->setBase($this->page);
		$paginator->setItemsPerPage($itemsPerPage);
		$paginator->setItemCount($this->count());
		$this->paginator = $paginator;
		$this->rewind();
	}

	public function count() : int
	{
		return iterator_count($this->collection);
	}

	public function handleRedirect(string $fragment = NULL)
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

	public function next()
	{
		$this->iterator++;
	}

	public function key() : int
	{
		return $this->iterator;
	}

	public function valid() : bool
	{
		return $this->iterator <= $this->paginator->getLastPage();
	}

	public function current() : IteratorAggregate
	{
		$this->paginator->setPage($this->iterator);
		$collection = $this->getCollection();
		$this->paginator->setPage($this->page);

		return $collection;
	}

	public function getCollection() : IteratorAggregate
	{
		return new ArrayObject(
			array_slice(
				iterator_to_array($this->collection),
				$this->paginator->getOffset(),
				$this->paginator->getItemsPerPage(),
				TRUE
			)
		);
	}

	public function getPaginator() : Nette\Utils\Paginator
	{
		return $this->paginator;
	}

	public function rewind()
	{
		$this->iterator = $this->paginator->getBase();
	}

	public function getCacheKey() : array
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

	public function loadState(array $params)
	{
		parent::loadState($params);
		$this->paginator->setPage($this->page);
	}

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

	protected function startup() : array
	{
		return [
			'paginator' => $this->paginator,
		];
	}

	protected function getViews() : array
	{
		return [
			'view' => function () {
				return [
					$this,
				];
			},
		] + parent::getViews();
	}
}
