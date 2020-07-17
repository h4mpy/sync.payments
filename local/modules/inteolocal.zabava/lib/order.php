<?php


namespace Inteolocal\Zabava;

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Result;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\PaySystem\Manager;
use Bitrix\Main\Type;

/**
 * Class Order
 * @package Inteolocal\Zabava
 */
class Order
{

	/**
	 * Метод добавляет в корзину товар с комментариями
	 * @param  int  $itemId
	 * @param  int  $quantity
	 * @param  string  $details
	 * @param  bool  $clearBasket
	 * @param  string  $detailPropertyCode
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function addBasketCustomItem($itemId = 0, $quantity = 1, $details = '', $clearBasket = false, $detailPropertyCode = 'CART_COMMENT')
	{
		$result = new Result();
		\Bitrix\Main\Loader::includeModule('sale');
		if (intval($itemId) > 0)
		{
			$basket = Basket::loadItemsForFUser(Fuser::getId(), Context::getCurrent()->getSite());
			if ($clearBasket)
			{
				$basket->clearCollection();
			}
			$item = $basket->createItem('catalog', $itemId);
			$item->setFields(array(
				'QUANTITY' => intval($quantity),
				'CURRENCY' => CurrencyManager::getBaseCurrency(),
				'LID' => Context::getCurrent()->getSite(),
				'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'
			));
			$basketPropertyCollection = $item->getPropertyCollection();
			$basketPropertyCollection->setProperty(array(
				array(
					'CODE' => $detailPropertyCode,
					'VALUE' => strval($details)
				),
			));
			$basket->refresh();
			$basket->save();
		}
		else
		{
			$result->addError(
				new Error(Loc::getMessage("INTEOLOCAL_WRONG_ID"), 'INTEOLOCAL_ZABAVA_ORDER_ADD_ERROR')
			);
		}
		return $result;
	}

	/**
	 * Метод возвращает массив товаров заказа для выгрузки БУС -> Б24
	 * @param \Bitrix\Sale\Order $order
	 * @return array
	 * @link https://dev.1c-bitrix.ru/rest_help/crm/cdeals/crm_deal_productrows_set.php
	 */
	public static function getProductRows(\Bitrix\Sale\Order $order)
	{
		$basket = $order->getBasket();
		$basketItems = $basket->getBasketItems();
		$shopItems = array();
		if (is_array($basketItems) && count($basketItems) > 0)
		{
			foreach ($basketItems as $basketItem)
			{
				$shopItems[] = array(
					'XML_ID' => $basketItem->getField('PRODUCT_XML_ID'), 'NAME' => $basketItem->getField('NAME'), 'QUANTITY' => $basketItem->getQuantity(), 'PRICE' => $basketItem->getPrice()
				);
			}
		}
		$basketItemsCrm = \CRest::call('crm.product.list', [
			'order' => [
				'NAME' => 'ASC'
			], 'filter' => [
				'XML_ID' => array_column($shopItems, 'XML_ID')
			], 'select' => [
				"ID", "NAME", "XML_ID"
			]
		]);
		$crmItems = (is_array($basketItemsCrm['result'])) ? $basketItemsCrm['result'] : array();

		$xmlIdtoId = array();
		$productRows = array();
		foreach ($crmItems as $crmItem)
		{
			$xmlIdtoId[$crmItem['XML_ID']] = $crmItem['ID'];
		}
		foreach ($shopItems as $shopItem)
		{
			$productRow = array(
				'PRICE' => $shopItem['PRICE'], 'QUANTITY' => $shopItem['QUANTITY']
			);
			if (isset($xmlIdtoId[$shopItem['XML_ID']]))
			{
				$productRow['PRODUCT_ID'] = $xmlIdtoId[$shopItem['XML_ID']];
			}
			else
			{
				$productRow['PRODUCT_NAME'] = $shopItem['NAME'];
			}
			$productRows[] = $productRow;
		}
		return $productRows;
	}

	/**
	 * Метод создает сделку в Б24 при создании заказа в БУС
	 * @param \Bitrix\Sale\Order $order
	 */
	public static function saveDealFromOrder(\Bitrix\Sale\Order $order)
	{
		$dealId = Deal::getByOrder($order);
		if ($dealId && $dealId > 0)
		{
			Logger::addLogMessage("SAVE_DEAL_FROM_ORDER:DEAL_ALREADY_EXISTS:{$dealId}");
		}
		else
		{
			Logger::addLogMessage("SAVE_DEAL_FROM_ORDER:START");
			$contactId = Contact::getOrderContact($order);
			if ($contactId && $contactId > 0)
			{
				$arFields = array(
					"TITLE" => Loc::getMessage("INTEOLOCAL_ZABAVA_DEALTITLE") . $order->getId(), "TYPE_ID" => "GOODS", "STAGE_ID" => "NEW", "CONTACT_ID" => $contactId, "OPENED" => "Y", "ASSIGNED_BY_ID" => 1, "PROBABILITY" => 50, "CURRENCY_ID" => $order->getCurrency(), "OPPORTUNITY" => $order->getPrice(), "ORIGINATOR_ID" => $order->getSiteId(), "ORIGIN_ID" => $order->getId(), "COMMENTS" => $order->getField("USER_DESCRIPTION"),
				);

				$propertyCollection = $order->getPropertyCollection();
				foreach ($propertyCollection as $propertyValue)
				{
					$property = $propertyValue->getProperty();
					if ($property['CODE'] == 'ARRIVE')
					{
						$arriveDate = $propertyValue->getValue();
						if (isset($arriveDate) && $arriveDate != '')
						{
							$arFields["UF_CRM_AMO_292053"] = date(DATE_ATOM, strtotime($arriveDate));
						}
					}
					if ($property['CODE'] == 'REQUISITES')
					{
						$fileValue = $propertyValue->getValue();
						if (isset($fileValue['SRC']) && $fileValue['SRC'] != '')
						{
							$fileObject = new \Bitrix\Main\IO\File(\Bitrix\Main\Application::getDocumentRoot() . $fileValue['SRC']);
							$arFields["UF_CRM_1567419297870"] = array(
								'fileData' => array(
									urldecode($fileValue['ORIGINAL_NAME']), base64_encode($fileObject->getContents())
								)
							);
						}
					}
				}

				//TODO Remove payment system to app block
				$paymentCollection = $order->getPaymentCollection();
				foreach ($paymentCollection as $payment)
				{
					$arFields["COMMENTS"] = Loc::getMessage("INTEOLOCAL_ORDER_COMMENT_PAY") . $payment->getPaymentSystemName() . Loc::getMessage("INTEOLOCAL_ORDER_COMMENT_TEXT") . $arFields["COMMENTS"]. '. ';
				}

				$basket = $order->getBasket();
				foreach ($basket as $basketItem)
				{
					$basketPropertyCollection = $basketItem->getPropertyCollection();
					foreach ($basketPropertyCollection as $propertyItem)
					{
						if ($propertyItem->getField('CODE') == 'CART_COMMENT')
						{
							$arFields["COMMENTS"].= $basketItem->getField('NAME') . ' - ' . $propertyItem->getField('VALUE').'. ';
						}
					}
				}

				if ($dealId = Deal::addCrmDeal($arFields, true))
				{
					$productRows = self::getProductRows($order);
					\CRest::call('crm.deal.productrows.set', [
						'id' => $dealId, 'rows' => $productRows
					]);
					Logger::addLogMessage("CREATED_DEAL: {$dealId}");
				}
				else
				{
					Logger::addLogMessage(array("FAILED_TO_CREATE_DEAL" => $arFields));
				}
			}
			else
			{
				Logger::addLogMessage("FAILED_TO_CREATE_DEAL_AND_CONTACT_FOR_ORDER: {$order->getId()}");
			}
		}
	}

	public static function saveOrderFromDeal($dealId = 0, $createOrderIfNotExist = false, $createPayments = true)
	{
		$multiplier = 1000; //Множитель для избежания совпадения внутреннего id кастомного товара корзины Б24 и id товара в БУС
		$result = new Result();
		$site = SiteTable::getList(array('filter' => array('DEF' => 'Y')))->fetch();
		//TODO moduleId catalog или sale? в чем разница?
		$moduleId = 'catalog';
		$debugUpdatedShopItems = 0;
		$debugUpdatedCustomItems = 0;
		$debugAddedShopItems = 0;
		$debugAddedCustomItems = 0;
		$debugDeletedItems = 0;
		$hasDuplicates = false;
		$checkFields = array();
		if ($dealId > 0)
		{
			Loader::includeModule('sale');
			Loader::includeModule('iblock');

			$deal = \CRest::call('crm.deal.get', [
				'id' => $dealId
			]);
			if (isset($deal['result']['ORIGIN_ID']) && $deal['result']['ORIGIN_ID'] > 0 && $deal['result']['ORIGINATOR_ID'] == $site['LID'])
			{
				$order = \Bitrix\Sale\Order::load(intval($deal['result']['ORIGIN_ID']));
			}
			if ($order || $createOrderIfNotExist)
			{
				Logger::addLogMessage("SAVE_ORDER_FROM_DEAL_START: {$dealId}");
				$dealProducts = \CRest::call('crm.deal.productrows.get', [
					'id' => $dealId
				]);
				$checkFields['deal'] = $dealProducts['result'];
				$checkFields['arrive'] = (isset($deal['result']['UF_CRM_AMO_292053']) && $deal['result']['UF_CRM_AMO_292053']!='')?$deal['result']['UF_CRM_AMO_292053']:'';
				$dealProductXmlIds = array();
				$dealProductCustomIds = array();
				$shopCompareItems = array();
				$dealProductCheckCustomIds = array();
				$duplicate = array();
				foreach ($dealProducts['result'] as $value)
				{
					if ($value['PRODUCT_ID'] > 0)
					{
						if (isset($duplicate[$value['PRODUCT_ID']]))
						{
							$hasDuplicates = true;
						}
						else
						{
							$duplicate[$value['PRODUCT_ID']] = $value;
						}
					}
				}
				if ($hasDuplicates)
				{
					$dealProductCheckCustomIds = $dealProducts['result'];
				}
				else
				{
					$dealProductCheckCustomIds = array_filter($dealProducts['result'], function ($value) {
						return $value['PRODUCT_ID'] == 0;
					});
				}

				if (count($dealProductCheckCustomIds) > 0)
				{
					$dealProductPrepareCustomIds = array_column($dealProductCheckCustomIds, 'ID');
					foreach ($dealProductPrepareCustomIds as $dealProductPrepareCustomId)
					{
						$dealProductCustomIds[] = intval($dealProductPrepareCustomId) * $multiplier;
					}
					//$dealProductCustomIds = array_map(function($el, $multiplier) {return intval($el) * $multiplier;}, $dealProductPrepareCustomIds, $multiplier);
				}

				if (isset($dealProducts['result']) && count($dealProducts['result']) > 0 && !$hasDuplicates)
				{

					$dealProductIds = array_filter(array_column($dealProducts['result'], 'PRODUCT_ID'), function ($value) {
						return !is_null($value) && $value > 0;
					});
					if (count($dealProductIds) > 0)
					{
						$dealFullProducts = \CRest::call('crm.product.list', [
							'order' => ['ID' => 'ASC'], 'filter' => ['ID' => $dealProductIds], 'select' => ['ID', 'NAME', 'XML_ID']
						]);
						if (is_array($dealFullProducts['result']) && count($dealFullProducts['result']) > 0)
						{
							foreach ($dealFullProducts['result'] as $dealFullProduct)
							{
								$dealProductXmlIds[$dealFullProduct['ID']] = $dealFullProduct['XML_ID'];
							}

							if (isset($dealProductXmlIds) && count($dealProductXmlIds) > 0)
							{
								//TODO Get default price id
								$priceId = 1;
								$resShopCompareItems = \CIBlockElement::GetList(Array(), Array("XML_ID" => $dealProductXmlIds, "IBLOCK_ID" => 4), false, false, Array("ID", "XML_ID", "NAME", "PRICE_" . $priceId));
								while ($shopCompareItem = $resShopCompareItems->GetNext())
								{
									$shopCompareItems[$shopCompareItem['XML_ID']] = $shopCompareItem;
								}
							}
						}
					}
				}
				if ($order)
				{
					$propertyCollection = $order->getPropertyCollection();
					foreach ($propertyCollection as $propertyValue)
					{
						$property = $propertyValue->getProperty();
						if ($property['CODE'] == 'ARRIVE' && $propertyValue->getValue() != $checkFields['arrive'])
						{
							if ($checkFields['arrive']!='')
							{
								$propertyValue->setValue(DateTime::createFromTimestamp(strtotime($deal['result']['UF_CRM_AMO_292053']))->format("d.m.Y"));
							}
							else
							{
								$propertyValue->setValue('');
							}
						}
					}

					$basket = $order->getBasket();
					$checkFields['order'] = $basket->getListOfFormatText();
					$history = new History($order->getId(), $order::getClassName());
					if ($history->isChanged($checkFields))
					{
						$history->save();
						$basketItems = $basket->getBasketItems();
						$shopBasket = array();
						$shopCustomBasket = array();

						//TODO решить что делать с отгрузками, если на товары уже напечатан чек. Пока все отгрузки удаляются, тк чек авансовый
						/*
						$shipmentCollection = $order->getShipmentCollection();
						foreach ($shipmentCollection as $shipment)
						{
							if (!$shipment->isSystem()) $shipment->delete();
						}
						*/
						//delete items
						if (is_array($basketItems) && count($basketItems) > 0)
						{
							foreach ($basketItems as $basketItem)
							{
								$basketXmlId = $basketItem->getField('PRODUCT_XML_ID');
								if (in_array($basketXmlId, $dealProductXmlIds) && !$hasDuplicates)
								{
									$shopBasket[$basketXmlId] = $basketItem->getField('PRODUCT_ID');
								}
								elseif (in_array($basketItem->getProductId(), $dealProductCustomIds))
								{
									$shopCustomBasket[] = $basketItem->getProductId();
								}
								else
								{
									$basketItem->delete();
									$debugDeletedItems++;
								}
							}
						}

						//add/update items
						if (isset($dealProducts['result']) && count($dealProducts['result']) > 0)
						{

							foreach ($dealProducts['result'] as $dealProduct)
							{
								$basketQuantity = (empty($dealProduct['QUANTITY']) ? 1 : (float)$dealProduct['QUANTITY']);
								if ($basketQuantity <= 0) $basketQuantity = 1;

								if (isset($shopBasket[$dealProductXmlIds[$dealProduct['PRODUCT_ID']]]))
								{
									//update shop item
									if ($basketSingleItem = $basket->getExistsItem($moduleId, $shopBasket[$dealProductXmlIds[$dealProduct['PRODUCT_ID']]]))
									{
										$basketSingleItem->setField('QUANTITY', $basketQuantity);
										if ($basketSingleItem->getPrice() != (float)$dealProduct['PRICE'])
										{
											$basketSingleItem->setField('PRICE', (float)$dealProduct['PRICE']);
											$basketSingleItem->setField('CUSTOM_PRICE', 'Y');
										}
										$debugUpdatedShopItems++;
									}
								}
								elseif (in_array(intval($dealProduct['ID']) * $multiplier, $shopCustomBasket))
								{
									//update custom item
									if ($basketSingleItem = $basket->getExistsItem($moduleId, intval($dealProduct['ID']) * $multiplier))
									{
										$basketSingleItem->setField('NAME', $dealProduct['PRODUCT_NAME']);
										$basketSingleItem->setField('QUANTITY', $basketQuantity);
										$basketSingleItem->setField('PRICE', (float)$dealProduct['PRICE']);
										$debugUpdatedCustomItems++;
									}
								}
								else
								{
									//add item
									if (isset($shopCompareItems[$dealProductXmlIds[$dealProduct['PRODUCT_ID']]]))
									{
										//item exists in iblock
										$itemFromShop = $shopCompareItems[$dealProductXmlIds[$dealProduct['PRODUCT_ID']]];
										$basketItem = $basket->createItem($moduleId, $itemFromShop['ID']);

										$addItem = array(
											'QUANTITY' => $basketQuantity, //'CURRENCY' => CurrencyManager::getBaseCurrency(),
											//'NAME' => $dealProduct['PRODUCT_NAME'],
											//'PRICE' => (float)$dealProduct['PRICE'],
											'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'
										);
										if ($dealProduct['PRICE'] != $itemFromShop['PRICE' . $priceId])
										{
											$addItem['PRICE'] = (float)$dealProduct['PRICE'];
											$addItem['CUSTOM_PRICE'] = 'Y';
										}
										$basketItem->setFields($addItem);
										$debugAddedShopItems++;
									}
									else
									{
										//$randomId = new \Bitrix\Main\Type\RandomSequence($dealProduct['ID']);
										$addItem = array(
											'QUANTITY' => $basketQuantity, 'CURRENCY' => CurrencyManager::getBaseCurrency(), 'NAME' => $dealProduct['PRODUCT_NAME'], 'PRICE' => (float)$dealProduct['PRICE'], 'PRODUCT_PROVIDER_CLASS' => false, 'CUSTOM_PRICE' => 'Y'
										);
										//$basketItem = $basket->createItem($moduleId, $randomId->rand(1000000, 9999999));
										$basketItem = $basket->createItem($moduleId, intval($dealProduct['ID']) * $multiplier);
										$basketItem->setFields($addItem);
										$debugAddedCustomItems++;
									}

								}
							}
						}

						//TODO обновить отгрузку
						$debugBasket = $basket->refresh();
						//$setBasket = $basket->save();
						$setOrder = $order->doFinalAction();
						$order->setField('DATE_UPDATE', new Type\DateTime());
						$saveResult = $order->save();
						//Logger::addLogMessage(var_dump($result));
						Logger::addLogMessage("SAVE_ORDER_FROM_DEAL_UPDATED_ORDER: {$order->getId()}");
					}
					$result->setData(array('ORDER_ID' => $order->getId()));
				}
				else
				{
					if ($createOrderIfNotExist)
					{
						//create new
						$r = User::getUserFromContact($deal['result']['CONTACT_ID']);
						if ($r->isSuccess())
						{
							$orderUser = $r->getData();
							//$orderUser['FIELDS'] = \CUser::GetByID($orderUser['ID'])->Fetch();
							$basket = Basket::create($site['LID']);
							foreach ($dealProducts['result'] as $dealProduct)
							{
								$basketQuantity = (empty($dealProduct['QUANTITY']) ? 1 : (float)$dealProduct['QUANTITY']);
								if ($basketQuantity <= 0) $basketQuantity = 1;

								//add item
								if (isset($shopCompareItems[$dealProductXmlIds[$dealProduct['PRODUCT_ID']]]) && !$basket->getExistsItem($moduleId, $shopCompareItems[$dealProductXmlIds[$dealProduct['PRODUCT_ID']]]['ID']))
								{
									//item exists in iblock
									$itemFromShop = $shopCompareItems[$dealProductXmlIds[$dealProduct['PRODUCT_ID']]];
									$basketItem = $basket->createItem($moduleId, $itemFromShop['ID']);

									$addItem = array(
										'QUANTITY' => $basketQuantity,
										'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider'
									);
									if ($dealProduct['PRICE'] != $itemFromShop['PRICE' . $priceId])
									{
										$addItem['PRICE'] = (float)$dealProduct['PRICE'];
										$addItem['CUSTOM_PRICE'] = 'Y';
									}
									$basketItem->setFields($addItem);
									$debugAddedShopItems++;
								}
								else
								{
									//$randomId = new \Bitrix\Main\Type\RandomSequence($dealProduct['ID']);
									$addItem = array(
										'QUANTITY' => $basketQuantity, 'CURRENCY' => CurrencyManager::getBaseCurrency(), 'NAME' => $dealProduct['PRODUCT_NAME'], 'PRICE' => (float)$dealProduct['PRICE'], 'PRODUCT_PROVIDER_CLASS' => false, 'CUSTOM_PRICE' => 'Y'
									);
									//$basketItem = $basket->createItem($moduleId, $randomId->rand(1000000, 9999999));
									$basketItem = $basket->createItem($moduleId, intval($dealProduct['ID']) * $multiplier);
									$basketItem->setFields($addItem);
									$debugAddedCustomItems++;
								}
							}
							$basket->refresh();
							$newOrder = \Bitrix\Sale\Order::create($site['LID'], $orderUser['ID']);


							$additional = array('CRM_DEAL_ID' => $dealId);
							$newOrder->setField('ADDITIONAL_INFO', Json::encode($additional));
							//TODO Сделать заказ на физическое и юридическое лицо по данным контакта или вынести в настройку. Сейчас оформляется на физическое
							$newOrder->setPersonTypeId(2);
							$propertyCollection = $newOrder->getPropertyCollection();
							$phoneProp = $propertyCollection->getPhone();
							$phoneProp->setValue($orderUser['FIELDS']['PERSONAL_PHONE']);
							$nameProp = $propertyCollection->getPayerName();
							$nameProp->setValue(implode(' ',array($orderUser['FIELDS']['LAST_NAME'], $orderUser['FIELDS']['NAME'], $orderUser['FIELDS']['SECOND_NAME'])));
							if (isset($orderUser['FIELDS']['EMAIL']) && $orderUser['FIELDS']['EMAIL']!='')
							{
								$emailProp = $propertyCollection->getUserEmail();
								$emailProp->setValue($orderUser['FIELDS']['EMAIL']);
							}
							foreach ($propertyCollection as $propertyValue)
							{
								$property = $propertyValue->getProperty();
								if ($property['CODE'] == 'ARRIVE' && isset($deal['result']['UF_CRM_AMO_292053']) && $deal['result']['UF_CRM_AMO_292053']!='')
								{
									$propertyValue->setValue(DateTime::createFromTimestamp(strtotime($deal['result']['UF_CRM_AMO_292053']))->format("d.m.Y"));
								}
								if ($property['CODE'] == 'MAIL_SENT')
								{
									$propertyValue->setValue('N');
								}
							}

							$newOrder->setBasket($basket);
							$newOrder->doFinalAction(true);

							if ($createPayments)
							{
								$paymentCollection = $newOrder->getPaymentCollection();
								$payment = $paymentCollection->createItem(
									Manager::getObjectById(10) // ID платежной системы
								);

								$payment->setField("SUM", $newOrder->getPrice());
								$payment->setField("CURRENCY", $newOrder->getCurrency());
							}

							$saveResult = $newOrder->save();
							if ($saveResult->isSuccess())
							{
								\CRest::call(
									'crm.deal.update',
									[
										'id' => $dealId,
										'fields' => ['ORIGIN_ID' => $newOrder->getId(), 'ORIGINATOR_ID' => $site['LID']],
										'params' => ["REGISTER_SONET_EVENT" => "Y"]
									]
								);
								$result->setData(array('ORDER_ID' => $newOrder->getId()));
							}
							else
							{
								Logger::addLogMessage($saveResult->getErrors());
							}
						}
						else
						{
							Logger::addLogMessage($r->getErrors());
						}
					}
				}
			}
		}
		return $result;
	}

}