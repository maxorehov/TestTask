<?php declare(strict_types=1);

namespace Test\OrdersApi\Model;

use Test\OrdersApi\Api\OrdersApiInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\FilterBuilderFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class OrdersApi implements OrdersApiInterface
{

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private FilterBuilder $filterBuilder;

    /**
     * @var SortOrderBuilder
     */
    private SortOrderBuilder $sortOrderBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var FileFactory
     */
    private FileFactory $fileFactory;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilderFactory $filterBuilderFactory
     * @param SortOrderBuilderFactory $sortOrderBuilderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @return void
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilderFactory $filterBuilderFactory,
        SortOrderBuilderFactory $sortOrderBuilderFactory,
        OrderRepositoryInterface $orderRepository,
        FileFactory $fileFactory,
        Filesystem $filesystem
    )
    {
        $this->searchCriteriaBuilder = $searchCriteriaBuilderFactory->create();
        $this->filterBuilder = $filterBuilderFactory->create();
        $this->sortOrderBuilder = $sortOrderBuilderFactory->create();
        $this->orderRepository = $orderRepository;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Sales\Api\Data\OrderSearchResultInterface|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getLastOrders()
    {
        $filterByStatus = $this->filterBuilder
            ->setField('status')
            ->setValue('complete')
            ->setConditionType('eq')
            ->create();

        $sortById = $this->sortOrderBuilder
            ->setField('increment_id')
            ->setDirection('asc')
            ->create();

        $this->searchCriteriaBuilder->addFilters([$filterByStatus]);
        $this->searchCriteriaBuilder->addSortOrder($sortById);
        $this->searchCriteriaBuilder->setPageSize(20);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $orders = $this->orderRepository->getList($searchCriteria);

        $csvData = array();
        $csvData[] = array('Order ID', 'Customer Email', 'Order Total');
        foreach ($orders as $order) {
            $csvData[] = array(
                $order->getId(),
                $order->getCustomerEmail(),
                $order->getGrandTotal()
            );
        }

        $fileName = 'last-orders.csv';
        $filePath = 'export/' . $fileName;
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        $stream = $directory->openFile($filePath, 'w+');
        foreach ($csvData as $rowData) {
            $stream->writeCsv($rowData);
        }
        $stream->close();

        $content = [
            'type' => 'filename',
            'value' => $filePath,
            'rm' => true
        ];

        return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);

    }
}
