// confirm_registration.js 

document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const form = document.getElementById('confirmationForm');
    const specialNeedsYes = document.getElementById('special_yes');
    const specialNeedsNo = document.getElementById('special_no');
    const specialNeedsSection = document.getElementById('special_needs_details_section');
    const specialNeedsDetails = document.getElementById('special_needs_details');
    const relationshipRadios = document.querySelectorAll('input[name="relationship"]');
    const relationshipOtherSection = document.getElementById('relationship_other_section');
    const relationshipOtherInput = document.getElementById('relationship_other');

    // Show/hide special needs section
    function toggleSpecialNeedsSection() {
        if (specialNeedsYes && specialNeedsYes.checked) {
            if (specialNeedsSection) specialNeedsSection.style.display = 'block';
            if (specialNeedsDetails) specialNeedsDetails.required = true;
        } else {
            if (specialNeedsSection) specialNeedsSection.style.display = 'none';
            if (specialNeedsDetails) {
                specialNeedsDetails.required = false;
                specialNeedsDetails.value = '';
            }
        }
    }

    // Show/hide relationship other field
    function toggleRelationshipOtherSection() {
        const otherSelected = document.getElementById('rel_other');
        if (otherSelected && otherSelected.checked) {
            if (relationshipOtherSection) relationshipOtherSection.style.display = 'block';
            if (relationshipOtherInput) relationshipOtherInput.required = true;
        } else {
            if (relationshipOtherSection) relationshipOtherSection.style.display = 'none';
            if (relationshipOtherInput) {
                relationshipOtherInput.required = false;
                relationshipOtherInput.value = '';
            }
        }
    }

    // Event listeners
    if (specialNeedsYes) specialNeedsYes.addEventListener('change', toggleSpecialNeedsSection);
    if (specialNeedsNo) specialNeedsNo.addEventListener('change', toggleSpecialNeedsSection);

    relationshipRadios.forEach(radio => {
        radio.addEventListener('change', toggleRelationshipOtherSection);
    });

    // Initialize sections on page load
    toggleSpecialNeedsSection();
    toggleRelationshipOtherSection();
});
