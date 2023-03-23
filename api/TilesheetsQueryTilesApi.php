<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\ILoadBalancer;

class TilesheetsQueryTilesApi extends ApiQueryBase {
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
			'mod' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
			],
		];
	}

	public function getExamples() {
		return [
			'api.php?action=query&list=tiles&tslimit=50&tsmod=W',
			'api.php?action=query&list=tiles&tsfrom=15&tsmod=V',
		];
	}

	public function execute() {
		$limit = (int)$this->getParameter( 'limit' );
		$from = $this->getParameter( 'from' );
		$mod = $this->getParameter( 'mod' );
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

		$results = $dbr->select(
			'ext_tilesheet_items',
			'*',
			[
				"mod_name = {$dbr->addQuotes($mod)} OR {$dbr->addQuotes($mod)} = ''",
				"entry_id >= " . intval( $from ),
			],
			__METHOD__,
			[ 'LIMIT' => $limit + 1, ]
		);

		$ret = [];
		$count = 0;
		foreach ( $results as $res ) {
			$count++;
			if ( $count > $limit ) {
				$this->setContinueEnumParameter( 'from', $res->entry_id );
				break;
			}
			$ret[] = [
				'id' => intval( $res->entry_id ),
				'mod' => $res->mod_name,
				'name' => $res->item_name,
				'x' => intval( $res->x ),
				'y' => intval( $res->y ),
				'z' => intval( $res->z ),
			];
		}

		$this->getResult()->addValue( 'query', 'tiles', $ret );
	}
}
