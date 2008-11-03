<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::AOP;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

require_once('F3_FLOW3_AOP_MockPointcutExpressionParser.php');
require_once('Fixture/F3_FLOW3_Tests_AOP_Fixture_CustomFilter.php');
require_once('Fixture/F3_FLOW3_Tests_AOP_Fixture_EmptyClass.php');

/**
 * Testcase for the default AOP Pointcut Expression Parser implementation
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3::FLOW3::AOP::PointcutExpressionParserTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PointcutExpressionParserTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::AOP::PointcutExpressionParser
	 */
	protected $parser;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->parser = $this->componentManager->getComponent('F3::FLOW3::AOP::PointcutExpressionParser');
	}

	/**
	 * Checks if the parser throws an exception if the expression is no string
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function parserThrowsExceptionIfExpressionIsNotString() {
		try {
			$this->parser->parse(FALSE);
		} catch (::Exception $exception) {
			$this->assertEquals(1168874738, $exception->getCode(), 'The pointcut expression parser throwed an exception but with the wrong error code.');
			return;
		}
		$this->fail('The pointcut expression parser throwed no exception although the expression was no string.');
	}

	/**
	 * Checks if the parser throws an exception if the pointcut function in the second part of the expression is missing.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function missingPointcutFunctionThrowsException() {
		try {
			$this->parser->parse('method(F3::TestPackage::BasicClass->*(..)) || ())');
		} catch (::Exception $exception) {
			$this->assertEquals(1168874739, $exception->getCode(), 'The pointcut expression parser throwed an exception but with the wrong error code.');
			return;
		}
		$this->fail('The pointcut expression parser throwed no exception although the expression was invalid.');
	}

	/**
	 * Checks if the parser throws an exception if no "->" is found in the signature pattern
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function missingArrowInSignaturePatternThrowsException() {
		try {
			$this->parser->parse('method(F3::TestPackage::BasicClass)');
		} catch (::Exception $exception) {
			$this->assertEquals(1169027339, $exception->getCode(), 'The pointcut expression parser throwed an exception but with the wrong error code.');
			return;
		}
		$this->fail('The pointcut expression parser throwed no exception although the expression was invalid.');
	}

	/**
	 * Checks if the parser parses an expression with two simple classes connected by an || operator
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function simpleClassWithOrOperatorIsParsedCorrectly() {
		$expectedPointcutFilterComposite = new F3::FLOW3::AOP::PointcutFilterComposite();

		$firstSubComposite = new F3::FLOW3::AOP::PointcutFilterComposite();
		$firstSubComposite->addFilter('&&', new F3::FLOW3::AOP::PointcutClassFilter('F3::TestPackage::BasicClass'));
		$firstSubComposite->addFilter('&&', new F3::FLOW3::AOP::PointcutMethodNameFilter('*'));

		$secondSubComposite = new F3::FLOW3::AOP::PointcutFilterComposite();
		$secondSubComposite->addFilter('&&', new F3::FLOW3::AOP::PointcutClassFilter('F3::TestPackage::BasicClass'));
		$secondSubComposite->addFilter('&&', new F3::FLOW3::AOP::PointcutMethodNameFilter('get*'));

		$expectedPointcutFilterComposite->addFilter('&&', $firstSubComposite);
		$expectedPointcutFilterComposite->addFilter('||', $secondSubComposite);

		$actualPointcutFilterComposite = $this->parser->parse('method(F3::TestPackage::BasicClass->*(..)) || method(F3::TestPackage::BasicClass->get*(..))');
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite, 'The filter chain while parsing a simple class expression was not correct.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classTaggedWithDesignatorIsParsedCorrectly() {
		$expectedPointcutFilterComposite = new F3::FLOW3::AOP::PointcutFilterComposite();
		$expectedPointcutFilterComposite->addFilter('&&', new F3::FLOW3::AOP::PointcutClassTaggedWithFilter('someTag'));

		$actualPointcutFilterComposite = $this->parser->parse('classTaggedWith(someTag)');
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function customFilterDesignatorIsParsedCorrectly() {
		$parser = new F3::FLOW3::AOP::MockPointcutExpressionParser($this->componentManager);

		$expectedPointcutFilterComposite = new F3::FLOW3::AOP::PointcutFilterComposite();
		$expectedPointcutFilterComposite->addFilter('&&', new F3::FLOW3::Tests::AOP::Fixture::CustomFilter());

		$actualPointcutFilterComposite = $parser->parse('filter(F3::FLOW3::Tests::AOP::Fixture::CustomFilter)');
		$this->assertEquals($expectedPointcutFilterComposite, $actualPointcutFilterComposite);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifACustomFilterDoesNotImplementThePointcutFilterInterfaceAnExceptionIsThrown() {
		$parser = new F3::FLOW3::AOP::MockPointcutExpressionParser($this->componentManager);

		$expectedPointcutFilterComposite = new F3::FLOW3::AOP::PointcutFilterComposite();
		$expectedPointcutFilterComposite->addFilter('&&', new F3::FLOW3::Tests::AOP::Fixture::CustomFilter());

		try {
			$parser->parse('filter(F3::FLOW3::Tests::AOP::Fixture::EmptyClass)');
			$this->fail('No exception was thrown.');
		} catch (::Exception  $exception) {

		}
	}
}
?>