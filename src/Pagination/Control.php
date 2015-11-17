<?php
namespace Ytnuk\Pagination;

use ArrayObject;
use Countable;
use IteratorAggregate;
use Nette;
use Traversable;
use Ytnuk;

class Control
	extends Ytnuk\Application\Control
	implements IteratorAggregate, Countable
{

	const NAME = 'pagination';

	/**
	 * @var int
	 * @persistent
	 */
	public $page = 1;

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
	}

	public function count() : int
	{
		return iterator_count($this->collection);
	}

	public function getIterator() : IteratorAggregate
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

	public function getCacheKey() : array
	{
		$key = parent::getCacheKey();
		$key[] = $this->paginator->getBase();
		$key[] = $this->paginator->getPage();
		$key[] = $this->paginator->getItemsPerPage();

		return $key;
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

	public function handleRedirect(string $fragment = NULL)
	{
		$parent = $this->lookup(
			Nette\Application\UI\IRenderable::class,
			FALSE
		);
		if ($parent instanceof Nette\Application\UI\IRenderable) {
			$parent->redrawControl();
		}
		parent::handleRedirect($fragment);
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
}
