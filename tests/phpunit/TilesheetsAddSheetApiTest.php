<?php

use MediaWiki\Permissions\Authority;
use MediaWiki\Session\SessionManager;

/**
 * @group Database
 * @covers TilesheetsAddSheetApi
 */
class TilesheetsAddSheetApiTest extends ApiTestCase {
	protected function setUp(): void {
		$this->tablesUsed[] = 'ext_tilesheet_images';
		$this->tablesUsed[] = 'logging';

		parent::setUp();

		$this->setGroupPermissions( [
			'sysop' => [
				'edittilesheets' => true,
			],
		] );
	}

	public function testShouldRejectUnauthorizedUsers(): void {
		$this->expectException( ApiUsageException::class );

		try {
			$user = $this->getTestUser()->getAuthority();
			$this->doAddSheetApiRequestWithToken( [
				'action' => 'createsheet',
				'tsmod' => 'mod',
				'tssizes' => '16|32',
			], $user );
		} catch ( ApiUsageException $e ) {
			$this->assertSame(
				'You do not have permission to create tilesheets',
				$e->getMessageObject()->getKey()
			);
			throw $e;
		}
	}

	public function testShouldRejectMissingToken(): void {
		$this->expectException( ApiUsageException::class );

		try {
			$user = $this->getTestSysop()->getAuthority();
			$this->doApiRequest( [
				'action' => 'createsheet',
				'tsmod' => 'mod',
				'tssizes' => '16|32',
				'tstoken' => 'bad'
			], performer: $user );
		} catch ( ApiUsageException $e ) {
			$this->assertSame(
				'apierror-badtoken',
				$e->getMessageObject()->getKey()
			);
			throw $e;
		}
	}

	public function testShouldCreateTilesheetWithoutSummary(): void {
		$user = $this->getTestSysop()->getAuthority();
		[ $result ] = $this->doAddSheetApiRequestWithToken( [
			'action' => 'createsheet',
			'tsmod' => 'mod',
			'tssizes' => '16|32',
		], $user );
		$this->assertSame( [ 'edit' => [ 'createsheet' => [ 'mod' => true ] ] ], $result );
		$this->assertTrue( $this->tileSheetExists( 'mod' ) );
	}

	public function testShouldCreateTilesheetWithSummary(): void {
		$user = $this->getTestSysop()->getAuthority();
		[ $result ] = $this->doAddSheetApiRequestWithToken( [
			'action' => 'createsheet',
			'tsmod' => 'mod2',
			'tssizes' => '16|32',
			'tssummary' => 'This mod rocks'
		], $user );
		$this->assertSame( [ 'edit' => [ 'createsheet' => [ 'mod2' => true ] ] ], $result );
		$this->assertTrue( $this->tileSheetExists( 'mod2' ) );
	}

	/**
	 * As {@link doApiRequestWithToken()}, but with support for a prefixed token parameter.
	 * @param array $params API parameters
	 * @param Authority $performer User performing the request
	 * @return array
	 */
	private function doAddSheetApiRequestWithToken(
		array $params, Authority $performer
	) {
		// From ApiTestCase::doApiRequest() but modified
		global $wgRequest;
		$session = $wgRequest->getSessionArray();
		$sessionObj = SessionManager::singleton()->getEmptySession();
		if ( $session !== null ) {
			foreach ( $session as $key => $value ) {
				$sessionObj->set( $key, $value );
			}
		}
		// set up global environment
		$legacyUser = $this->getServiceContainer()->getUserFactory()->newFromAuthority( $performer );
		$contextUser = $legacyUser;
		$sessionObj->setUser( $contextUser );
		$params['tstoken'] = ApiQueryTokens::getToken(
			$contextUser,
			$sessionObj,
			ApiQueryTokens::getTokenTypeSalts()['csrf']
		)->toString();
		return $this->doApiRequestWithToken(
			$params,
			iterator_to_array( $sessionObj ),
			$performer,
			null
		);
	}

	/**
	 * Check whether a tilesheet exists in the database.
	 * @param string $name Tilesheet name
	 * @return bool
	 */
	private function tileSheetExists( string $name ): bool {
		$lb = $this->getServiceContainer()->getDBLoadBalancer();
		$dbr = $lb->getConnection( $lb::DB_PRIMARY );

		$res = $dbr->newSelectQueryBuilder()
			->select( '1' )
			->from( 'ext_tilesheet_images' )
			->where( [ '`mod`' => $name ] )
			->caller( __METHOD__ )
			->fetchField();

		return $res !== false;
	}
}
