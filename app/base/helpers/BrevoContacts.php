<?php
declare(strict_types=1);
/**
 * Brevo Contacts Helper
 *
 * Manages contact creation and updates in Brevo contact database
 * Uses Brevo Contacts API to sync form submissions with Brevo lists
 *
 * @package App\Base\Helpers
 */

namespace App\Base\Helpers;

use Brevo\Client\Configuration;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\UpdateContact;
use GuzzleHttp\Client;

class BrevoContacts
{
    /**
     * Add or update a contact in Brevo
     *
     * @param array $contactData Contact information ['email', 'name', 'phone', 'service_interest', etc.]
     * @return bool True if successful, false otherwise
     */
    public static function addContact(array $contactData): bool
    {
        // Check if Brevo contacts integration is enabled
        if (!self::isEnabled()) {
            logger()->info('Brevo contacts integration is disabled, skipping contact creation');
            return true; // Not an error, just disabled
        }

        $apiKey = env('BREVO_API_KEY');
        $listId = self::getListId();

        if (!$apiKey) {
            logger()->error('Brevo API key not configured');
            return false;
        }

        if (!$listId) {
            logger()->error('Brevo list ID not configured');
            return false;
        }

        try {
            logger()->info('Creating/updating Brevo contact', [
                'email' => $contactData['email'] ?? 'N/A',
                'list_id' => $listId
            ]);

            // Configure Brevo API client
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $apiInstance = new ContactsApi(new Client(), $config);

            // Prepare contact attributes
            $attributes = self::prepareAttributes($contactData);

            // Create contact model
            $contact = new CreateContact([
                'email' => $contactData['email'],
                'attributes' => $attributes,
                'listIds' => [(int)$listId],
                'updateEnabled' => true // Update if contact already exists
            ]);

            // Create or update contact
            $result = $apiInstance->createContact($contact);

            logger()->info('Brevo contact created/updated successfully', [
                'email' => $contactData['email'],
                'contact_id' => $result->getId() ?? 'updated'
            ]);

            return true;

        } catch (ApiException $e) {
            // Check if error is because contact already exists
            $responseBody = $e->getResponseBody();
            $errorData = json_decode($responseBody, true);

            if ($e->getCode() === 400 && isset($errorData['code']) && $errorData['code'] === 'duplicate_parameter') {
                // Contact exists, try to update instead
                logger()->info('Contact already exists, updating instead', [
                    'email' => $contactData['email']
                ]);

                return self::updateContact($contactData);
            }

            logger()->error('Brevo Contacts API Exception', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'response' => $responseBody
            ]);

            return false;

        } catch (\Exception $e) {
            logger()->error('Brevo contact creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return false;
        }
    }

    /**
     * Update an existing contact in Brevo
     *
     * @param array $contactData Contact information
     * @return bool True if successful, false otherwise
     */
    private static function updateContact(array $contactData): bool
    {
        $apiKey = env('BREVO_API_KEY');
        $listId = self::getListId();

        try {
            // Configure Brevo API client
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
            $apiInstance = new ContactsApi(new Client(), $config);

            // Prepare contact attributes
            $attributes = self::prepareAttributes($contactData);

            // Update contact model
            $contact = new UpdateContact([
                'attributes' => $attributes,
                'listIds' => [(int)$listId]
            ]);

            // Update contact
            $apiInstance->updateContact($contactData['email'], $contact);

            logger()->info('Brevo contact updated successfully', [
                'email' => $contactData['email']
            ]);

            return true;

        } catch (\Exception $e) {
            logger()->error('Brevo contact update failed', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Prepare contact attributes from form data
     *
     * @param array $data Form data
     * @return array Formatted attributes for Brevo
     */
    private static function prepareAttributes(array $data): array
    {
        $attributes = [];

        // Map form fields to Brevo attributes
        // Brevo requires specific attribute names (can be configured in Brevo dashboard)
        if (!empty($data['name'])) {
            // Try to split name into first and last
            $nameParts = explode(' ', trim($data['name']), 2);
            $attributes['FIRSTNAME'] = $nameParts[0];
            if (isset($nameParts[1])) {
                $attributes['LASTNAME'] = $nameParts[1];
            }
        }

        if (!empty($data['phone'])) {
            $attributes['SMS'] = $data['phone'];
        }

        if (!empty($data['service_interest'])) {
            $attributes['SERVICE_INTEREST'] = $data['service_interest'];
        }

        if (!empty($data['subject'])) {
            $attributes['SUBJECT'] = $data['subject'];
        }

        if (!empty($data['message'])) {
            // Limit message length for attribute storage
            $attributes['MESSAGE'] = substr($data['message'], 0, 500);
        }

        // Add submission timestamp
        $attributes['LAST_CONTACT_DATE'] = date('Y-m-d H:i:s');

        return $attributes;
    }

    /**
     * Check if Brevo contacts integration is enabled
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return env('BREVO_CONTACTS_ENABLED', 'true') === 'true';
    }

    /**
     * Get Brevo list ID from configuration
     *
     * @return int|null
     */
    public static function getListId(): ?int
    {
        $listId = env('BREVO_LIST_ID', '');
        return $listId !== '' ? (int)$listId : null;
    }
}
