<?php

namespace IWD\All\Model;

class Support extends \Magento\Sales\Model\AbstractModel
{
    const SupportEmail = 'extensions@iwdagency.com';
    const SupportName = 'Support';

    protected $result = '';
    protected $_moduleList;
    protected $_cacheTypeList;
    protected $config;
    protected $statesFactory;
    protected $indexerFactory;

    protected $_params;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Indexer\ConfigInterface $config,
        \Magento\Indexer\Model\ResourceModel\Indexer\State\CollectionFactory $statesFactory,
        \Magento\Framework\Indexer\IndexerInterface $indexerFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_moduleList = $moduleList;
        $this->_cacheTypeList = $cacheTypeList;
        $this->config = $config;
        $this->statesFactory = $statesFactory;
        $this->indexerFactory = $indexerFactory;
    }

    public function sendTicket($params)
    {
        $this->initParams($params);
        $this->collectInfo();
        $this->sendEmail();
    }

    protected function initParams($params)
    {
        if(!isset($params['email']) || !isset($params['name']) || !isset($params['type']) || !isset($params['description'])){
            throw new \Exception('Incorrect params');
        }

        $from = $this->_formatAddress($params['email'], $params['name']);
        $to = $this->_formatAddress(self::SupportEmail, self::SupportName);
        $extension = isset($params['extension']) && !empty($params['extension']) ? ': ' . $params['extension'] : '';

        $this->_params = [
            'from' => $from,
            'to' => $to,
            'subj' => $params['type'] . $extension,
            'description' => $params['description'],
        ];
    }

    protected function _formatAddress($email, $name)
    {
        if ($name === '' || $name === null || $name === $email) {
            return $email;
        }

        return sprintf('%s <%s>', $name, $email);
    }

    protected function collectInfo()
    {
        $this->result = '<table>';

        $this->result .= "<tr><td colspan='2'>{$this->_params['description']}</td></tr>";

        $this->MagentoInfo();
        $this->CacheInfo();
        $this->IndexInfo();
        $this->InformationAboutExtensions();
        $this->MySqlInfo();
        $this->ConfigInfo();
        $this->ExtensionsInfo();

        $this->result .= '</table>';
    }

    private function MagentoInfo()
    {
        $this->addRowToTable("Magento information");

        $this->addRowToTable('Magento version', \Magento\Framework\AppInterface::VERSION);
        $this->addRowToTable('Magento mode', $this->_appState->getMode());
        $this->addRowToTable('Domain', $_SERVER ["HTTP_HOST"]);
        $this->addRowToTable('Path', strstr(realpath(__FILE__),'IWD',true));
    }

    private function CacheInfo()
    {
        $this->addRowToTable("Magento: Cache Storage Management");

        foreach ($this->_cacheTypeList->getTypes() as $type) {
            $this->addRowToTable($type['cache_type'], $type['status']);
        }
    }

    private function IndexInfo()
    {
        $this->addRowToTable("Magento: Index Management");

        $states = $this->statesFactory->create();

        foreach (array_keys($this->config->getIndexers()) as $indexerId) {
            /** @var \Magento\Framework\Indexer\IndexerInterface $indexer */
            $indexer = $this->indexerFactory->load($indexerId);
            foreach ($states->getItems() as $state) {
                /** @var \Magento\Indexer\Model\Indexer\State $state */
                if ($state->getIndexerId() == $indexerId) {
                    $indexer->setState($state);
                    break;
                }
            }

            $info = "STATUS: {$indexer->getState()->getStatus()},<br />
            UPDATE AT: {$indexer->getLatestUpdated()}";

            $this->addRowToTable($indexer->getTitle(), $info);
        }
    }

    public function InformationAboutExtensions()
    {
        $this->addRowToTable('Advanced modules');

        $modules = $this->_moduleList->getNames();

        $dispatchResult = new \Magento\Framework\DataObject($modules);
        $this->_eventManager->dispatch(
            'adminhtml_system_config_advanced_disableoutput_render_before',
            ['modules' => $dispatchResult]
        );
        $modules = $dispatchResult->toArray();

        sort($modules);
        foreach ($modules as $moduleName) {
            if(strpos(strtolower($moduleName), 'magento') !== 0){
                @$info = $this->_moduleList->getOne($moduleName);
                $this->addRowToTable($moduleName, @$info['setup_version']);
            }
        }
    }

    private function MySqlInfo()
    {
        $this->addRowToTable("MySql information");
        preg_match('/[0-9]\.[0-9]+\.[0-9]+/', shell_exec('mysql -V'), $version);
        if (empty($version [0]) || empty($version [0])) {
            $this->addRowToTable('MySql version', 'N/A');
        } else {
            $this->addRowToTable('MySql version', $version [0]);
        }
    }

    private function ConfigInfo()
    {
        $this->addRowToTable("Configuration");
        $this->addRowToTable('PHP version', phpversion());
        $ini = array('safe_mode', 'memory_limit', 'realpath_cache_ttl','allow_url_fopen');

        foreach ($ini as $i) {
            $val = ini_get($i);
            $val = empty ($val) ? 'off' : $val;
            $this->addRowToTable($i, $val);
        }
    }

    private function ExtensionsInfo()
    {
        $this->addRowToTable("PHP Extensions");
        $extensions = array(
            'curl',
            'dom',
            'gd',
            'hash',
            'iconv',
            'mcrypt',
            'pcre',
            'pdo',
            'pdo_mysql',
            'simplexml'
        );
        foreach ($extensions as $extension){
            $this->addRowToTable($extension, extension_loaded($extension));
        }
    }

    protected function sendEmail()
    {
        $from = $this->_params['from'];
        $to = $this->_params['to'];
        $subj = $this->_params['subj'];
        $text = $this->result;

        $un = strtoupper(uniqid(time()));
        $head =
            "Reply-To: $from\n" .
            "Mime-Version: 1.0\n" .
            "Content-Type:multipart/mixed;" .
            "boundary=\"----------".$un."\"\n\n";

        $additional =
            "------------".$un."\nContent-Type:text/html;\n" .
            "Content-Transfer-Encoding: 8bit\n\n$text\n\n";

        $attachment = $_FILES['attachments'];

        for ($i = 0; $i < count($attachment ['name']); $i++) {
            $tmpFilePath = $attachment['tmp_name'][$i];
            $fileName = $attachment['name'][$i];
            if ($tmpFilePath != "") {
                $f = fopen($tmpFilePath,"rb");
                $additional .=
                    "------------".$un."\n" .
                    "Content-Type: application/octet-stream;" .
                    "name=\"".basename($fileName)."\"\n" .
                    "Content-Transfer-Encoding:base64\n" .
                    "Content-Disposition:attachment;" .
                    "filename=\"".basename($fileName)."\"\n\n" .
                    chunk_split(base64_encode(fread($f,filesize($tmpFilePath))))."\n";
            }
        }


        $result = @mail("$to", "$subj", $additional, $head);
        if(!$result){
            throw new \Exception('Email was not send!');
        }
    }

    private function addRowToTable($column1, $column2 = "")
    {
        if ($column2 === "") {
            $this->result .= '<tr><td colspan="2" align="center"><b>' . $column1 . '</b></td></tr>';
        } else {
            $this->result .= '<tr><td>' . $column1 . '</td><td>' . $column2 . '</td></tr>';
        }
    }
}