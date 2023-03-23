<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\ILoadBalancer;

class TilesheetsQueryTileUsagesApi extends ApiQueryBase {
	public function __construct(
		$query,
		$moduleName,
		private ILoadBalancer $loadBalancer
	) {
		parent::__construct( $query, $moduleName, 'ts' );
	}

	public function getAllowedParams() {
		return [
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
			],
			'from' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_DEFAULT => 0,
			],
			'tile' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
				ApiBase::PARAM_MIN => 0,
			],
			'namespace' => [
				ApiBase::PARAM_TYPE => 'namespace',
				ApiBase::PARAM_ISMULTI => true,
			],
		];
	}

	public function getExamples() {
		return [
			'api.php?action=query&list=tileusages&tslimit=50&tstile=20',
			'api.php?action=query&list=tileusages&tsfrom=15&tstile=400',
		];
	}

	public function execute() {
		$limit = (int)$this->getParameter( 'limit' );
		$from = $this->getParameter( 'from' );
		$tileID = $this->getParameter( 'tile' );
		$namespaces = $this->getParameter( 'namespace' );
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$conditions = [
			'tl_to = ' . intval( $tileID ),
			'tl_from >= ' . intval( $from ),
		];

		if ( !empty( $namespaces ) ) {
			$namespaceConditions = [];
			foreach ( $namespaces as $ns ) {
				$namespaceConditions[] = "tl_from_namespace = " . intval( $ns );
			}
			$conditions[] = $dbr->makeList( $namespaceConditions, LIST_OR );
		}

		$results = $dbr->select(
			'ext_tilesheet_tilelinks',
			'*',
			$conditions,
			__METHOD__,
			[ 'LIMIT' => $limit + 1 ]
		);

		$ret = [];
		$count = 0;
		foreach ( $results as $res ) {
			$count++;
			if ( $count > $limit ) {
				$this->setContinueEnumParameter( 'from', $res->tl_from );
				break;
			}
			$pageName = $dbr->select(
				'page',
				'page_title',
				[
					'page_id' => $res->tl_from,
					'page_namespace' => $res->tl_from_namespace,
				],
				__METHOD__
			)->current()->page_title;
			$title = Title::newFromText( $pageName, $res->tl_from_namespace );

			$ret[] = [
				'entryid' => $res->tl_to,
				'pageid' => $res->tl_from,
				'ns' => $res->tl_from_namespace,
				'title' => $title->getPrefixedText(),
			];
		}

		$this->getResult()->addValue( 'query', 'tileusages', $ret );
	}
}
