<?php
/**
 * Copyright Total Processing. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace TotalProcessing\Opp\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use TotalProcessing\Opp\Gateway\ErrorMapper\VirtualErrorMessageMapper;
use TotalProcessing\Opp\Gateway\Helper\SuccessCode;
use TotalProcessing\Opp\Gateway\Response\CommonHandler;
use TotalProcessing\Opp\Gateway\Response\TransactionCheckHandler;
use TotalProcessing\Opp\Gateway\SubjectReader;

/**
 * Class TransactionCheckValidator
 * @package TotalProcessing\Opp\Gateway\Validator
 */
class TransactionCheckValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;
        $errorCodes = [];

        foreach ($this->getStatements($validationSubject) as $statement) {
            if (!$statement['statement']) {
                $isValid = false;

                if (isset($statement['errorCode'])) {
                    $errorCodes[] = $statement['errorCode'];
                }
            }
        }

        return $this->createResult($isValid, [], $errorCodes);
    }

    /**
     * Returns validator statements
     *
     * @param array $validationSubject
     * @return array[]
     */
    protected function getStatements(array $validationSubject): array
    {
        //@TODO - statements response doesn't take into account
        $response = $this->subjectReader->readResponse($validationSubject['response'] ?? []);
        return [];
    }
}
