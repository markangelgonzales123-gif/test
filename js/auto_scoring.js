// Calculate average rating from Quality, Efficiency, and Timeliness scores
function calculateAverageRating(quality, efficiency, timeliness) {
    // Validate inputs
    quality = parseFloat(quality) || 0;
    efficiency = parseFloat(efficiency) || 0;
    timeliness = parseFloat(timeliness) || 0;
    
    // Calculate average
    const average = (quality + efficiency + timeliness) / 3;
    
    // Return formatted to 2 decimal places
    return average.toFixed(2);
}

// Get rating interpretation based on average score
function getRatingInterpretation(averageScore) {
    const score = parseFloat(averageScore);
    
    if (score >= 4.5) return "Outstanding";
    if (score >= 3.5) return "Very Satisfactory";
    if (score >= 2.5) return "Satisfactory";
    if (score >= 1.5) return "Unsatisfactory";
    return "Poor";
}

// Calculate final weighted rating based on category weights
function calculateFinalRating(strategicAverage, coreAverage, supportAverage = null, computationType = 'Type1') {
    let finalRating;
    
    // Type1: Strategic (45%) and Core (55%)
    if (computationType === 'Type1') {
        finalRating = (strategicAverage * 0.45) + (coreAverage * 0.55);
    } 
    // Type2: Strategic (45%), Core (45%), and Support (10%)
    else if (computationType === 'Type2' && supportAverage !== null) {
        finalRating = (strategicAverage * 0.45) + (coreAverage * 0.45) + (supportAverage * 0.10);
    } else {
        // Default to Type1 if computation type is invalid or support average is missing
        finalRating = (strategicAverage * 0.45) + (coreAverage * 0.55);
    }
    
    return finalRating.toFixed(2);
}

// Attach event listeners to rating inputs in a table row
function attachRatingListeners() {
    // For all quality, efficiency, and timeliness inputs
    document.querySelectorAll('.rating-input').forEach(input => {
        input.addEventListener('input', function() {
            // Get data attributes to identify the entry
            const index = this.dataset.index;
            const type = this.dataset.type;  // strategic, core, or support
            
            // Get all three rating inputs for this entry
            const qInput = document.querySelector(`input[name="${type}_q[]"][data-index="${index}"]`);
            const eInput = document.querySelector(`input[name="${type}_e[]"][data-index="${index}"]`);
            const tInput = document.querySelector(`input[name="${type}_t[]"][data-index="${index}"]`);
            const averageField = document.querySelector(`input[name="${type}_a[]"][data-index="${index}"]`);
            
            if (qInput && eInput && tInput && averageField) {
                if (qInput.value && eInput.value && tInput.value) {
                    const average = calculateAverageRating(qInput.value, eInput.value, tInput.value);
                    averageField.value = average;
                    
                    // Update any interpretation field if it exists
                    const interpretationField = document.querySelector(`.${type}-interpretation[data-index="${index}"]`);
                    if (interpretationField) {
                        interpretationField.textContent = getRatingInterpretation(average);
                    }
                }
            }
            
            // Recalculate category averages and final rating
            updateFinalRating();
        });
    });
}

// Calculate and update category averages and final rating
function updateFinalRating() {
    // Get computation type
    const computationType = document.getElementById('computation_type')?.value || 'Type1';
    
    // Calculate strategic average
    let strategicTotal = 0;
    let strategicCount = 0;
    document.querySelectorAll('input[name="strategic_a[]"]').forEach(input => {
        if (input.value) {
            strategicTotal += parseFloat(input.value);
            strategicCount++;
        }
    });
    const strategicAverage = strategicCount > 0 ? strategicTotal / strategicCount : 0;
    
    // Calculate core average
    let coreTotal = 0;
    let coreCount = 0;
    document.querySelectorAll('input[name="core_a[]"]').forEach(input => {
        if (input.value) {
            coreTotal += parseFloat(input.value);
            coreCount++;
        }
    });
    const coreAverage = coreCount > 0 ? coreTotal / coreCount : 0;
    
    // Calculate support average (if applicable)
    let supportAverage = 0;
    if (computationType === 'Type2') {
        let supportTotal = 0;
        let supportCount = 0;
        document.querySelectorAll('input[name="support_a[]"]').forEach(input => {
            if (input.value) {
                supportTotal += parseFloat(input.value);
                supportCount++;
            }
        });
        supportAverage = supportCount > 0 ? supportTotal / supportCount : 0;
    }
    
    // Update category average fields if they exist
    if (document.getElementById('strategic_average')) {
        document.getElementById('strategic_average').value = strategicAverage.toFixed(2);
    }
    if (document.getElementById('core_average')) {
        document.getElementById('core_average').value = coreAverage.toFixed(2);
    }
    if (document.getElementById('support_average')) {
        document.getElementById('support_average').value = supportAverage.toFixed(2);
    }
    
    // Calculate and update final rating
    const finalRating = calculateFinalRating(strategicAverage, coreAverage, supportAverage, computationType);
    if (document.getElementById('final_rating')) {
        document.getElementById('final_rating').value = finalRating;
    }
    
    // Update rating interpretation
    if (document.getElementById('rating_interpretation')) {
        document.getElementById('rating_interpretation').textContent = getRatingInterpretation(finalRating);
    }
}

// Initialize when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Attach event listeners to all rating inputs
    attachRatingListeners();
    
    // Initial calculation of final rating
    updateFinalRating();
    
    // Listen for computation type changes
    const computationTypeSelect = document.getElementById('computation_type');
    if (computationTypeSelect) {
        computationTypeSelect.addEventListener('change', updateFinalRating);
    }
}); 


//Added field formatting and restrictions
document.addEventListener('DOMContentLoaded', function() {
    // Select all your rating inputs
    const ratingInputs = document.querySelectorAll('.rating-input');

    ratingInputs.forEach(input => {
        // Use the 'input' event to catch typing, pasting, and auto-filling
        input.addEventListener('input', function() {
            // 1. Regex Clean-up: Only keep characters '1' through '5'
            // [^1-5] means "match anything that is NOT a 1, 2, 3, 4, or 5"
            // The /g flag means "globally" (replace all occurrences)
            this.value = this.value.replace(/[^1-5]/g, '');

            // 2. Enforce Single Character Length
            // If the cleaned value is still longer than 1 (e.g., if '5' was pasted)
            if (this.value.length > 1) {
                // Truncate the value to the very first character
                this.value = this.value.slice(0, 1);
            }
        });
        
        // 3. Block unwanted keys on keydown (prevents 'e', '.', etc. from ever appearing)
        input.addEventListener('keydown', function(event) {
            // Allow control keys (Backspace, Tab, Delete, Arrows)
            if (event.key === 'Backspace' || 
                event.key === 'Delete' || 
                event.key.startsWith('Arrow') || 
                event.key === 'Tab' || 
                event.key === 'Home' || 
                event.key === 'End'
            ) {
                return;
            }

            // Block 'e', '.', '+', '-', and ','
            if (event.key === 'e' || 
                event.key === '.' || 
                event.key === '+' || 
                event.key === '-' ||
                event.key === ','
            ) {
                event.preventDefault(); 
            }
        });
    });
});