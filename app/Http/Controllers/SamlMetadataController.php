<?php

namespace App\Http\Controllers;

use DOMDocument;

class SamlMetadataController extends Controller
{
    public function __invoke()
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $entity = $document->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'md:EntityDescriptor');
        $entity->setAttribute('entityID', (string) config('services.saml.sp_entity_id'));
        $document->appendChild($entity);
        $sp = $document->createElement('md:SPSSODescriptor');
        $sp->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:2.0:protocol');
        // AuthnRequests are currently unsigned; the IdP trust certificate must
        // never be advertised as an SP signing certificate.
        $sp->setAttribute('AuthnRequestsSigned', 'false');
        $sp->setAttribute('WantAssertionsSigned', config('services.saml.sign_assertions') ? 'true' : 'false');
        $entity->appendChild($sp);
        $sp->appendChild($document->createElement('md:NameIDFormat', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'));
        $slo = $document->createElement('md:SingleLogoutService');
        $slo->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
        $slo->setAttribute('Location', url('/saml2/logout'));
        $sp->appendChild($slo);
        $acs = $document->createElement('md:AssertionConsumerService');
        $acs->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST');
        $acs->setAttribute('Location', url('/saml2/acs'));
        $acs->setAttribute('index', '1');
        $acs->setAttribute('isDefault', 'true');
        $sp->appendChild($acs);

        return response($document->saveXML(), 200, ['Content-Type' => 'application/samlmetadata+xml']);
    }
}
