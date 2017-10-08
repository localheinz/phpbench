<?php

namespace PhpBench\Tests\Unit\Assertion;

use PHPUnit\Framework\TestCase;
use PhpBench\Assertion\ComparatorAsserter;
use PhpBench\Math\Distribution;
use PhpBench\Registry\Config;
use PhpBench\Assertion\AssertionFailure;
use PhpBench\Assertion\AsserterRegistry;
use PhpBench\DependencyInjection\Container;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComparatorAsserterTest extends TestCase
{
    const COMPARATOR_ASSERTION = 'comparator';

    /**
     * @dataProvider provideComparison
     */
    public function testComparison(array $samples, array $config, string $failureMessage = null)
    {
        if ($failureMessage) {
            $this->expectException(AssertionFailure::class);
            $this->expectExceptionMessage($failureMessage);
        }

        $this->assert(new Distribution($samples), $config);

        if (null === $failureMessage) {
            $this->addToAssertionCount(1);
        }
    }

    public function provideComparison()
    {
        return [
            [
                [ 10, 10 ],
                [
                    ComparatorAsserter::OPTION_STAT => 'mean',
                    ComparatorAsserter::OPTION_VALUE => 15,

                ],
            ],
            [
                [ 10, 10 ],
                [
                    ComparatorAsserter::OPTION_STAT => 'mean',
                    ComparatorAsserter::OPTION_VALUE => 5
                ],
                'mean is not less than 5, it was 10',
            ],
            [
                [ 2, 2 ],
                [
                    ComparatorAsserter::OPTION_COMPARATOR => '>',
                    ComparatorAsserter::OPTION_STAT => 'mean',
                    ComparatorAsserter::OPTION_VALUE => 5,
                ],
                'mean is not greater than 5, it was 2',
            ],
        ];
    }

    private function assert(Distribution $distribution, array $config = [])
    {
        $assertion = new ComparatorAsserter();
        $optionsResolver = new OptionsResolver();
        $assertion->configure($optionsResolver);
        $config = $optionsResolver->resolve($config);

        $assertion->assert($distribution, new Config('test', $config));
    }
}