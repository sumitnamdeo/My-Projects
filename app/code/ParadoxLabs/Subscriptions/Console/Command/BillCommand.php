<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\Subscriptions\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * BillCommand Class
 */
class BillCommand extends Command
{
    /**
     * @var \ParadoxLabs\Subscriptions\Model\Cron\Bill
     */
    protected $command;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param \ParadoxLabs\Subscriptions\Model\Cron\Bill $command
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\Subscriptions\Model\Cron\Bill $command,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct();

        $this->command = $command;
        $this->appState = $appState;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Set up command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('subscriptions:bill')
             ->setDescription('Bill any outstanding subscriptions.');

        parent::configure();
    }

    /**
     * Checks whether the command is enabled or not in the current environment.
     *
     * @return bool
     */
    public function isEnabled()
    {
        $moduleActive = $this->scopeConfig->getValue(
            'subscriptions/general/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($moduleActive == 1) {
            return parent::isEnabled();
        }

        return false;
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $output->writeln((string)__('Running any outstanding subscriptions.'));

        $startTime = microtime(true);

        $this->command->setConsoleOutput($output);
        $this->command->runSubscriptions();

        $output->writeln((string)__('Total runtime: %1 sec.', microtime(true) - $startTime));
    }
}
