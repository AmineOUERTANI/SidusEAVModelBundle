<?php
/*
 * This file is part of the Sidus/EAVModelBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVModelBundle\Context;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Manager for setting, saving and getting the current context when using ContextualData & ContextualValue
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface ContextManagerInterface
{
    /**
     * @return array
     */
    public function getContext();

    /**
     * This method is exposed only for command-line applications, please use the context selector form
     *
     * @param array $context
     *
     * @internal Warning, this method will save the context without any checks on the values
     */
    public function setContext(array $context);

    /**
     * @return array
     */
    public function getDefaultContext();
}
