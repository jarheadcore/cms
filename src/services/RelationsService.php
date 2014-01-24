<?php
namespace Craft;

/**
 *
 */
class RelationsService extends BaseApplicationComponent
{
	/**
	 * Saves a relation field.
	 *
	 * @param BaseElementFieldType $fieldType
	 * @throws \Exception
	 * @return bool
	 */
	public function saveField(BaseElementFieldType $fieldType)
	{
		$source = $fieldType->element;
		$field = $fieldType->model;
		$targetIds = $source->getContent()->getAttribute($field->handle);

		if ($targetIds === null)
		{
			return true;
		}

		if (!is_array($targetIds))
		{
			$targetIds = array();
		}

		// Prevent duplicate target IDs.
		$targetIds = array_unique($targetIds);

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the existing relations
			$oldRelationConditions = array('and', 'fieldId = :fieldId', 'sourceId = :sourceId');
			$oldRelationParams = array(':fieldId' => $field->id, ':sourceId' => $source->id);

			if ($field->translatable)
			{
				$oldRelationConditions[] = array('or', 'sourceLocale is null', 'sourceLocale = :sourceLocale');
				$oldRelationParams[':sourceLocale'] = $source->locale;
			}

			craft()->db->createCommand()->delete('relations', $oldRelationConditions, $oldRelationParams);

			// Add the new ones
			if ($targetIds)
			{
				$values = array();

				if ($field->translatable)
				{
					$sourceLocale = $source->locale;
				}
				else
				{
					$sourceLocale = null;
				}

				foreach ($targetIds as $sortOrder => $targetId)
				{
					$values[] = array($field->id, $source->id, $sourceLocale, $targetId, $sortOrder+1);
				}

				$columns = array('fieldId', 'sourceId', 'sourceLocale', 'targetId', 'sortOrder');
				craft()->db->createCommand()->insertAll('relations', $columns, $values);
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}
}
